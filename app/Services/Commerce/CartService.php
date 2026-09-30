<?php

namespace App\Services\Commerce;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Inventory\InsufficientStockException;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

/**
 * Cart ownership and mutation.
 *
 * A guest holds a cart identified by an opaque cookie token; on login that cart
 * is merged into the user's own cart. Prices are never stored on a cart line —
 * every total is recalculated from the live product at read time, so a stale or
 * tampered client value can never decide what is charged.
 *
 * Administrators do not shop, and are refused a cart entirely.
 */
class CartService
{
    public const COOKIE = 'hanbell_cart';

    public function __construct(private readonly InventoryService $inventory) {}

    /* ------------------------------------------------------------------ *
     * Resolving the active cart
     * ------------------------------------------------------------------ */

    /**
     * The cart for the current visitor, creating one only when needed.
     */
    public function current(bool $create = true): ?Cart
    {
        $user = auth()->user();

        if ($user instanceof User) {
            if (! $user->canShop()) {
                return null;
            }

            $cart = Cart::firstOrCreate(['user_id' => $user->id]);

            // A guest token may still be present if login happened without the
            // merge running (e.g. a resumed session).
            $this->mergeTokenInto($cart);

            return $cart;
        }

        $token = request()->cookie(self::COOKIE);

        if (blank($token)) {
            return $create ? $this->createGuestCart() : null;
        }

        $cart = Cart::where('guest_token', $token)->first();

        return $cart ?? ($create ? $this->createGuestCart() : null);
    }

    public function createGuestCart(): Cart
    {
        $cart = Cart::create([
            'guest_token' => Cart::newGuestToken(),
            'currency' => $this->displayCurrency(),
        ]);

        // Long-lived, HTTP-only: the token identifies a cart, so it must not be
        // readable from JavaScript.
        Cookie::queue(cookie()->make(self::COOKIE, $cart->guest_token, 60 * 24 * 30, null, null, false, true));

        return $cart;
    }

    /* ------------------------------------------------------------------ *
     * Mutation
     * ------------------------------------------------------------------ */

    /**
     * Add a product (optionally a specific variant) to the cart.
     */
    public function add(
        Product $product,
        ?ProductVariant $variant,
        int $quantity = 1,
        ?Cart $cart = null,
    ): CartItem {
        $quantity = max(1, $quantity);
        $cart ??= $this->current();

        if ($cart === null) {
            throw new \RuntimeException('Admins cannot add items to a cart.');
        }

        $this->guardPurchasable($product, $variant, $quantity);

        return DB::transaction(function () use ($cart, $product, $variant, $quantity) {
            $item = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->where('variant_id', $variant?->id)
                ->lockForUpdate()
                ->first();

            if ($item) {
                $newQuantity = $item->quantity + $quantity;

                $this->guardPurchasable($product, $variant, $newQuantity);

                $item->update(['quantity' => $newQuantity]);
            } else {
                $item = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'vendor_id' => $product->vendor_id,
                    'quantity' => $quantity,
                ]);
            }

            $cart->touchActivity();

            return $item;
        });
    }

    /**
     * Set a line's quantity. Zero removes the line.
     */
    public function updateQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->remove($item);

            return;
        }

        $product = $item->product;

        if ($product) {
            $this->guardPurchasable($product, $item->variant, $quantity);
        }

        $item->update(['quantity' => $quantity]);
        $item->cart?->touchActivity();
    }

    public function remove(CartItem $item): void
    {
        $cart = $item->cart;
        $item->delete();
        $cart?->touchActivity();
    }

    public function clear(?Cart $cart = null): void
    {
        $cart ??= $this->current(create: false);

        if ($cart === null) {
            return;
        }

        $cart->items()->delete();
        $cart->touchActivity();
    }

    /**
     * Remove every line whose product is no longer purchasable.
     *
     * @return int number of lines dropped
     */
    public function pruneUnavailable(Cart $cart): int
    {
        $removed = 0;

        foreach ($cart->items()->with(['product.vendor', 'variant'])->get() as $item) {
            $product = $item->product;

            if (! $product || ! $product->isPublished() || $product->vendor?->isApproved() !== true) {
                $item->delete();
                $removed++;

                continue;
            }

            $available = $product->availableQuantity($item->variant);

            if ($available <= 0 && $item->variant?->inventory?->allow_backorder !== true) {
                $item->delete();
                $removed++;
            } elseif ($available > 0 && $item->quantity > $available) {
                // Trim to what is actually left rather than dropping the line.
                $item->update(['quantity' => $available]);
            }
        }

        return $removed;
    }

    /* ------------------------------------------------------------------ *
     * Totals — always derived, never stored
     * ------------------------------------------------------------------ */

    /**
     * @return array{
     *     subtotal_minor:int,
     *     item_count:int,
     *     line_count:int,
     *     items:array<int,array<string,mixed>>,
     *     by_vendor:array<int,array<string,mixed>>
     * }
     */
    public function summary(?Cart $cart = null, ?int $shippingMinor = null, int $discountMinor = 0): array
    {
        $cart ??= $this->current(create: false);

        if ($cart === null) {
            return [
                'subtotal_minor' => 0,
                'item_count' => 0,
                'line_count' => 0,
                'items' => [],
                'by_vendor' => [],
            ];
        }

        $cart->loadMissing(['items.product.vendor', 'items.product.media', 'items.variant.inventory']);

        $lines = [];
        $byVendor = [];
        $subtotal = 0;
        $itemCount = 0;

        foreach ($cart->items as $item) {
            $product = $item->product;

            if (! $product) {
                continue;
            }

            $unit = $item->unitPriceMinor();
            $total = $unit * $item->quantity;

            $subtotal += $total;
            $itemCount += $item->quantity;

            $line = [
                'id' => $item->uuid,
                'cart_item_id' => $item->id,
                'product' => $product,
                'variant' => $item->variant,
                'quantity' => $item->quantity,
                'unit_price_minor' => $unit,
                'line_total_minor' => $total,
                'available' => $product->availableQuantity($item->variant),
                'url' => $product->publicUrl(),
            ];

            $lines[] = $line;

            $vendorId = $product->vendor_id;

            if (! isset($byVendor[$vendorId])) {
                $byVendor[$vendorId] = [
                    'vendor' => $product->vendor,
                    'items' => [],
                    'subtotal_minor' => 0,
                ];
            }

            $byVendor[$vendorId]['items'][] = $line;
            $byVendor[$vendorId]['subtotal_minor'] += $total;
        }

        $shipping = $shippingMinor ?? $this->shippingFor($subtotal);
        $discount = max(0, min($discountMinor, $subtotal));

        return [
            'subtotal_minor' => $subtotal,
            'discount_minor' => $discount,
            'shipping_minor' => $shipping,
            'total_minor' => max(0, $subtotal - $discount) + $shipping,
            'item_count' => $itemCount,
            'line_count' => count($lines),
            'items' => $lines,
            'by_vendor' => array_values($byVendor),
        ];
    }

    /**
     * Flat shipping, free above the configured threshold.
     * Shipping is charged once per order, not per vendor, at this stage.
     */
    public function shippingFor(int $subtotalMinor): int
    {
        if ($subtotalMinor <= 0) {
            return 0;
        }

        $freeThreshold = (int) config('hanbell.shipping.free_threshold_minor', 5000000);

        if ($freeThreshold > 0 && $subtotalMinor >= $freeThreshold) {
            return 0;
        }

        return (int) config('hanbell.shipping.flat_minor', 200000);
    }

    public function amountUntilFreeShipping(int $subtotalMinor): int
    {
        $threshold = (int) config('hanbell.shipping.free_threshold_minor', 5000000);

        return max(0, $threshold - $subtotalMinor);
    }

    public function qualifiesForFreeShipping(int $subtotalMinor): bool
    {
        return $this->shippingFor($subtotalMinor) === 0 && $subtotalMinor > 0;
    }

    /* ------------------------------------------------------------------ *
     * Guest → user merge
     * ------------------------------------------------------------------ */

    /**
     * Fold a guest cart into a user's cart after login or registration.
     * Quantities are summed and re-validated against live stock.
     */
    public function mergeGuestCartInto(User $user, ?string $guestToken): void
    {
        if (blank($guestToken) || ! $user->canShop()) {
            return;
        }

        $guestCart = Cart::where('guest_token', $guestToken)->with('items')->first();

        if (! $guestCart || $guestCart->items->isEmpty()) {
            $guestCart?->delete();

            return;
        }

        $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

        DB::transaction(function () use ($guestCart, $userCart): void {
            foreach ($guestCart->items as $guestItem) {
                $product = $guestItem->product;

                if (! $product || ! $product->isPublished()) {
                    continue;
                }

                $existing = CartItem::where('cart_id', $userCart->id)
                    ->where('product_id', $guestItem->product_id)
                    ->where('variant_id', $guestItem->variant_id)
                    ->first();

                $desired = ($existing?->quantity ?? 0) + $guestItem->quantity;
                $available = $product->availableQuantity($guestItem->variant);

                // Never merge past what is actually in stock.
                $quantity = $available > 0 ? min($desired, $available) : $desired;

                if ($existing) {
                    $existing->update(['quantity' => $quantity]);
                } else {
                    CartItem::create([
                        'cart_id' => $userCart->id,
                        'product_id' => $guestItem->product_id,
                        'variant_id' => $guestItem->variant_id,
                        'vendor_id' => $guestItem->vendor_id,
                        'quantity' => $quantity,
                    ]);
                }
            }

            $guestCart->items()->delete();
            $guestCart->delete();
            $userCart->touchActivity();
        });

        Cookie::queue(Cookie::forget(self::COOKIE));
    }

    /** Merge a leftover guest cart when its token is still in the request. */
    private function mergeTokenInto(Cart $cart): void
    {
        $token = request()->cookie(self::COOKIE);

        if (blank($token)) {
            return;
        }

        $guest = Cart::where('guest_token', $token)->where('id', '!=', $cart->id)->first();

        if (! $guest) {
            return;
        }

        if ($guest->items()->exists() && $cart->items()->exists() === false) {
            $guest->items()->update(['cart_id' => $cart->id]);
            $guest->delete();
        }

        Cookie::queue(Cookie::forget(self::COOKIE));
    }

    /* ------------------------------------------------------------------ *
     * Guards
     * ------------------------------------------------------------------ */

    private function guardPurchasable(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        if (! $product->isPublished()) {
            throw new \RuntimeException('This piece is no longer available.');
        }

        if ($product->vendor?->isApproved() === false) {
            throw new \RuntimeException('This brand is not currently selling on HanbellShop.');
        }

        if ($variant && $variant->product_id !== $product->id) {
            throw new \RuntimeException('That option does not belong to this piece.');
        }

        $available = $product->availableQuantity($variant);

        if ($available <= 0 && $variant?->inventory?->allow_backorder !== true) {
            throw new InsufficientStockException($product, $variant, $quantity, 0);
        }

        if ($available > 0 && $quantity > $available) {
            throw new InsufficientStockException($product, $variant, $quantity, $available);
        }
    }

    private function displayCurrency(): string
    {
        return (string) (auth()->user()?->currency ?: config('hanbell.currency.default', 'NGN'));
    }

    /** Vendors represented in the current cart — used for the ad exclusion rule. */
    public function vendorIdsInCart(?Cart $cart = null): array
    {
        $cart ??= $this->current(create: false);

        if ($cart === null) {
            return [];
        }

        return $cart->items()->distinct()->pluck('vendor_id')->filter()->all();
    }

    /** Vendor model lookup helper for views. */
    public function vendorFor(int $vendorId): ?Vendor
    {
        return Vendor::find($vendorId);
    }
}
