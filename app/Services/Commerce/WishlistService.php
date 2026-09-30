<?php

namespace App\Services\Commerce;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Support\Facades\Cookie;

/**
 * Wishlist ownership mirrors the cart: a guest holds one via an opaque cookie
 * token, and it is merged into the user's list on login.
 */
class WishlistService
{
    public const COOKIE = 'hanbell_wishlist';

    public function current(bool $create = true): ?Wishlist
    {
        $user = auth()->user();

        if ($user instanceof User) {
            if (! $user->canShop()) {
                return null;
            }

            return Wishlist::firstOrCreate(['user_id' => $user->id]);
        }

        $token = request()->cookie(self::COOKIE);

        if (blank($token)) {
            return $create ? $this->createGuestWishlist() : null;
        }

        return Wishlist::where('guest_token', $token)->first()
            ?? ($create ? $this->createGuestWishlist() : null);
    }

    public function createGuestWishlist(): Wishlist
    {
        $wishlist = Wishlist::create(['guest_token' => \Illuminate\Support\Str::random(48)]);

        Cookie::queue(cookie()->make(self::COOKIE, $wishlist->guest_token, 60 * 24 * 30, null, null, false, true));

        return $wishlist;
    }

    /** Toggle a product's presence. Returns true when it was added. */
    public function toggle(Product $product, ?ProductVariant $variant = null): bool
    {
        $wishlist = $this->current();

        if ($wishlist === null) {
            throw new \RuntimeException('Admins do not have a wishlist.');
        }

        $existing = WishlistItem::where('wishlist_id', $wishlist->id)
            ->where('product_id', $product->id)
            ->where('variant_id', $variant?->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        WishlistItem::create([
            'wishlist_id' => $wishlist->id,
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
        ]);

        return true;
    }

    public function has(Product $product, ?ProductVariant $variant = null): bool
    {
        $wishlist = $this->current(create: false);

        if ($wishlist === null) {
            return false;
        }

        return WishlistItem::where('wishlist_id', $wishlist->id)
            ->where('product_id', $product->id)
            ->where('variant_id', $variant?->id)
            ->exists();
    }

    public function remove(WishlistItem $item): void
    {
        $item->delete();
    }

    public function count(): int
    {
        return $this->current(create: false)?->itemCount() ?? 0;
    }

    /** @return array<int,int> product ids currently wishlisted */
    public function productIds(): array
    {
        $wishlist = $this->current(create: false);

        return $wishlist
            ? WishlistItem::where('wishlist_id', $wishlist->id)->pluck('product_id')->all()
            : [];
    }

    public function mergeGuestWishlistInto(User $user, ?string $guestToken): void
    {
        if (blank($guestToken) || ! $user->canShop()) {
            return;
        }

        $guest = Wishlist::where('guest_token', $guestToken)->with('items')->first();

        if (! $guest) {
            Cookie::queue(Cookie::forget(self::COOKIE));

            return;
        }

        if ($guest->items->isNotEmpty()) {
            $userWishlist = Wishlist::firstOrCreate(['user_id' => $user->id]);

            foreach ($guest->items as $item) {
                WishlistItem::firstOrCreate([
                    'wishlist_id' => $userWishlist->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                ]);
            }
        }

        $guest->items()->delete();
        $guest->delete();

        Cookie::queue(Cookie::forget(self::COOKIE));
    }
}
