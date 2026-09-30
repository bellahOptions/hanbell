<?php

namespace App\Services\Commerce;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderPaid;
use App\Events\OrderPlaced;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorOrder;
use App\Services\Inventory\InventoryService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Turns a cart into an order.
 *
 * Everything price-bearing is snapshotted onto the order at this moment —
 * product name, variant label, SKU, unit price, commission rate — so editing or
 * deleting a product later can never rewrite what a customer was charged.
 *
 * Stock is *reserved* here, not deducted. Deduction happens in markPaid() once
 * a payment has been verified, so an abandoned or failed checkout releases its
 * hold instead of permanently consuming stock.
 */
class OrderService
{
    public function __construct(
        private readonly CartService $carts,
        private readonly InventoryService $inventory,
    ) {}

    /**
     * Create an order from the current cart.
     *
     * Runs in a single transaction: if any line cannot be reserved, the whole
     * order rolls back rather than being created half-stocked.
     *
     * @param  array<string,mixed>  $shipping  shipping address fields
     */
    public function createFromCart(
        Cart $cart,
        array $shipping,
        array $contact,
        ?Coupon $coupon = null,
        ?string $notes = null,
        ?User $user = null,
        OrderChannel $channel = OrderChannel::Web,
    ): Order {
        /*
         * `product.category` is eager-loaded because commission resolution reads
         * the category override for every line. Without it this is one extra
         * query per item — and with `preventLazyLoading` enabled outside
         * production, a hard failure rather than a slow checkout.
         */
        $cart->loadMissing([
            'items.product.vendor',
            'items.product.category',
            'items.product.media',
            'items.variant',
        ]);

        if ($cart->items->isEmpty()) {
            throw new RuntimeException('Your bag is empty.');
        }

        $summary = $this->carts->summary($cart, shippingMinor: 0);

        if ($summary['subtotal_minor'] <= 0) {
            throw new RuntimeException('Your bag has no purchasable items.');
        }

        $discount = $coupon?->discountFor($summary['subtotal_minor']) ?? 0;
        $shippingMinor = $this->resolveShippingAmount($shipping, $summary['subtotal_minor']);
        $taxMinor = $this->resolveTaxAmount($summary['subtotal_minor'] - $discount);

        $total = max(0, $summary['subtotal_minor'] - $discount) + $shippingMinor + $taxMinor;

        return DB::transaction(function () use (
            $cart, $summary, $shipping, $contact, $coupon, $notes, $user, $channel,
            $discount, $shippingMinor, $taxMinor, $total
        ): Order {
            $order = Order::create([
                'number' => Order::generateNumber(),
                'user_id' => $user?->id ?? $cart->user_id,
                'coupon_id' => $coupon?->id,
                'email' => $contact['email'],
                'phone' => $contact['phone'] ?? null,
                'customer_name' => $contact['name'],
                'shipping_recipient_name' => $shipping['recipient_name'],
                'shipping_phone' => $shipping['phone'],
                'shipping_line1' => $shipping['line1'],
                'shipping_line2' => $shipping['line2'] ?? null,
                'shipping_city' => $shipping['city'],
                'shipping_state' => $shipping['state'],
                'shipping_postal_code' => $shipping['postal_code'] ?? null,
                'shipping_country' => $shipping['country'] ?? 'NG',
                'notes' => $notes,
                'currency' => $cart->currency ?: config('hanbell.currency.default', 'NGN'),
                'subtotal_minor' => $summary['subtotal_minor'],
                'discount_minor' => $discount,
                'shipping_minor' => $shippingMinor,
                'tax_minor' => $taxMinor,
                'total_minor' => $total,
                'commission_minor' => 0,
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Pending,
                'channel' => $channel,
            ]);

            // Allocate the order-level discount across vendors in proportion to
            // their subtotal, so per-vendor commission is computed on what the
            // customer actually pays for that vendor's goods.
            $vendorWeights = array_map(
                fn (array $group) => $group['subtotal_minor'],
                $summary['by_vendor'],
            );
            $vendorDiscounts = Money::allocate($discount, $vendorWeights);

            $totalCommission = 0;
            $index = 0;

            foreach ($summary['by_vendor'] as $group) {
                $vendor = $group['vendor'];
                $vendorSubtotal = $group['subtotal_minor'];

                if (! $vendor || ! $vendor->isApproved()) {
                    throw new RuntimeException(
                        sprintf('"%s" is no longer selling on HanbellShop.', $vendor?->name ?? 'A brand')
                    );
                }

                $commissionPercent = $this->commissionPercentFor($vendor, $group['items'][0]['product']->category ?? null);
                $vendorDiscount = $vendorDiscounts[$index] ?? 0;

                // Commission is charged on the discounted vendor subtotal.
                $commissionBase = max(0, $vendorSubtotal - $vendorDiscount);
                $vendorCommission = Money::percentOf($commissionBase, $commissionPercent);

                $vendorOrder = VendorOrder::create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendor->id,
                    'number' => $order->number.'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'subtotal_minor' => $vendorSubtotal,
                    'shipping_minor' => 0,
                    'commission_minor' => $vendorCommission,
                    'payout_minor' => $commissionBase - $vendorCommission,
                    'commission_percent' => $commissionPercent,
                    'status' => OrderStatus::Pending,
                ]);

                foreach ($group['items'] as $line) {
                    /** @var Product $product */
                    $product = $line['product'];
                    $variant = $line['variant'];
                    $quantity = (int) $line['quantity'];

                    // Reserve stock inside the same transaction: a failure here
                    // rolls the entire order back.
                    $this->inventory->reserve($product, $variant, $quantity, $order);

                    $lineCommission = $quantity === 0
                        ? 0
                        : (int) round($vendorCommission * ($line['line_total_minor'] / max(1, $vendorSubtotal)));

                    OrderItem::create([
                        'order_id' => $order->id,
                        'vendor_order_id' => $vendorOrder->id,
                        'vendor_id' => $vendor->id,
                        'product_id' => $product->id,
                        'variant_id' => $variant?->id,
                        'product_name' => $product->name,
                        'variant_label' => $variant?->label(),
                        'sku' => $variant?->sku ?: $product->sku,
                        'vendor_name' => $vendor->name,
                        'image_path' => $product->primaryImage()?->path,
                        'quantity' => $quantity,
                        'unit_price_minor' => $line['unit_price_minor'],
                        'line_total_minor' => $line['line_total_minor'],
                        'commission_minor' => $lineCommission,
                        'commission_percent' => $commissionPercent,
                    ]);
                }

                $totalCommission += $vendorCommission;
                $index++;
            }

            $order->forceFill(['commission_minor' => $totalCommission])->save();

            if ($coupon) {
                $coupon->increment('used_count');
            }

            $cart->items()->delete();

            event(new OrderPlaced($order));

            return $order->refresh();
        });
    }

    /**
     * Convert a verified payment into a paid order.
     *
     * Idempotent: the `paid_at` guard means a replayed webhook or a double
     * browser callback cannot deduct stock or fire OrderPaid twice.
     */
    public function markPaid(Order $order, ?string $providerReference = null): Order
    {
        if ($order->paid_at !== null) {
            return $order;
        }

        return DB::transaction(function () use ($order, $providerReference): Order {
            /*
             * Eager-load the product with its variant and inventory: committing
             * a sale touches each line's stock, and lazy-loading there is three
             * queries per item inside a locked transaction — the worst place to
             * spend time.
             */
            /** @var Order $fresh */
            $fresh = Order::whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail()
                ->load(['items.product.inventories', 'items.variant.inventory']);

            if ($fresh->paid_at !== null) {
                return $fresh;
            }

            foreach ($fresh->items as $item) {
                $product = $item->product;

                if (! $product) {
                    // The product was deleted after the order was placed; the
                    // order snapshot still stands, so there is nothing to
                    // deduct against.
                    continue;
                }

                $this->inventory->commitSale(
                    $product,
                    $item->variant,
                    $item->quantity,
                    $fresh,
                );
            }

            $fresh->forceFill([
                'status' => OrderStatus::Paid,
                'payment_status' => PaymentStatus::Succeeded,
                'paid_at' => now(),
            ])->save();

            // Move each vendor sub-order forward too.
            $fresh->vendorOrders()->update([
                'status' => OrderStatus::Paid->value,
                'updated_at' => now(),
            ]);

            event(new OrderPaid($fresh));

            return $fresh->refresh();
        });
    }

    /**
     * Cancel an unpaid order, releasing every reservation it holds.
     */
    public function cancel(Order $order, ?string $reason = null): Order
    {
        if ($order->isPaid()) {
            throw new RuntimeException('A paid order must be refunded, not cancelled.');
        }

        return DB::transaction(function () use ($order, $reason): Order {
            foreach ($order->items as $item) {
                if ($product = $item->product) {
                    $this->inventory->release($product, $item->variant, $item->quantity, $order, $reason);
                }
            }

            $order->forceFill([
                'status' => OrderStatus::Cancelled,
                'payment_status' => PaymentStatus::Cancelled,
                'cancelled_at' => now(),
            ])->save();

            $order->vendorOrders()->update([
                'status' => OrderStatus::Cancelled->value,
                'updated_at' => now(),
            ]);

            return $order->refresh();
        });
    }

    /**
     * Refund a paid order: releases stock back and records the reversal.
     */
    public function refund(Order $order, ?string $reason = null): Order
    {
        if (! $order->isPaid()) {
            throw new RuntimeException('Only a paid order can be refunded.');
        }

        return DB::transaction(function () use ($order, $reason): Order {
            foreach ($order->items as $item) {
                if ($product = $item->product) {
                    $this->inventory->restock(
                        $product,
                        $item->variant,
                        $item->quantity,
                        $order,
                        auth()->id(),
                        $reason ?? 'Order refunded',
                        \App\Enums\InventoryMovementReason::Return,
                    );
                }
            }

            $order->forceFill([
                'status' => OrderStatus::Refunded,
                'payment_status' => PaymentStatus::Refunded,
            ])->save();

            $order->vendorOrders()->update([
                'status' => OrderStatus::Refunded->value,
                'updated_at' => now(),
            ]);

            return $order->refresh();
        });
    }

    /* ------------------------------------------------------------------ *
     * Commission
     * ------------------------------------------------------------------ */

    /**
     * Commission rate for a vendor's line, most specific first:
     * vendor override → category override → store default.
     */
    public function commissionPercentFor(\App\Models\Vendor $vendor, ?\App\Models\Category $category = null): float
    {
        return $vendor->commissionPercent()
            ?? $category?->commissionPercent()
            ?? (float) config('hanbell.commission.default_percent', 12);
    }

    /* ------------------------------------------------------------------ *
     * Totals
     * ------------------------------------------------------------------ */

    private function resolveShippingAmount(array $shipping, int $subtotalMinor): int
    {
        // Free-shipping threshold still applies to the goods subtotal.
        return $this->carts->shippingFor($subtotalMinor);
    }

    /**
     * Nigerian VAT is normally already inside the displayed price, so this only
     * produces a figure when `prices_include_tax` is switched off.
     */
    private function resolveTaxAmount(int $taxableMinor): int
    {
        if ((bool) config('hanbell.tax.prices_include_tax', true)) {
            return 0;
        }

        $rate = (float) config('hanbell.tax.vat_percent', 7.5);

        if ($rate <= 0) {
            return 0;
        }

        return Money::percentOf($taxableMinor, $rate);
    }

    /**
     * Recompute an order's stored totals from its lines.
     * Used by the admin order editor to keep totals honest after a manual edit.
     */
    public function recalculateTotals(Order $order): Order
    {
        $subtotal = (int) $order->items()->sum('line_total_minor');
        $discount = (int) $order->discount_minor;
        $shipping = (int) $order->shipping_minor;
        $tax = (int) $order->tax_minor;

        $order->forceFill([
            'subtotal_minor' => $subtotal,
            'commission_minor' => (int) $order->items()->sum('commission_minor'),
            'total_minor' => max(0, $subtotal - $discount) + $shipping + $tax,
        ])->save();

        return $order;
    }
}
