<?php

namespace App\Livewire\Storefront;

use App\Services\Commerce\CartService;
use App\Services\Commerce\WishlistService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The bag and wishlist badges in the header.
 *
 * Kept as its own component so counts update in place: every mutating action
 * elsewhere dispatches `cart-updated` / `wishlist-updated`, and this re-reads
 * them without a page reload.
 */
class HeaderCounts extends Component
{
    public int $cartCount = 0;

    public int $wishlistCount = 0;

    public bool $isAdmin = false;

    public function mount(CartService $carts, WishlistService $wishlist): void
    {
        $this->isAdmin = auth()->user()?->isAdmin() ?? false;

        if ($this->isAdmin) {
            return;
        }

        $this->cartCount = $carts->current(create: false)?->itemCount() ?? 0;
        $this->wishlistCount = $wishlist->count();
    }

    #[On('cart-updated')]
    public function refreshCart(CartService $carts): void
    {
        if ($this->isAdmin) {
            return;
        }

        $this->cartCount = $carts->current(create: false)?->itemCount() ?? 0;
    }

    #[On('wishlist-updated')]
    public function refreshWishlist(WishlistService $wishlist): void
    {
        if ($this->isAdmin) {
            return;
        }

        $this->wishlistCount = $wishlist->count();
    }

    public function render(): View
    {
        return view('livewire.storefront.header-counts');
    }
}
