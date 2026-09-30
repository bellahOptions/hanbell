<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\WishlistItem;
use App\Services\Commerce\WishlistService;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Wishlist extends Component
{
    use InteractsWithToasts;

    public function remove(int $itemId, WishlistService $wishlist): void
    {
        $list = $wishlist->current(create: false);

        if (! $list) {
            return;
        }

        // Scoped to this visitor's own list, so an id from the client cannot
        // delete someone else's saved item.
        $item = $list->items()->find($itemId);

        if (! $item) {
            return;
        }

        $item->delete();
        $this->dispatch('wishlist-updated');
        $this->toastSuccess(__('hanbell.product.remove_from_wishlist'));
    }

    /** Move a saved item straight into the bag. */
    public function moveToBag(int $itemId, WishlistService $wishlist, \App\Services\Commerce\CartService $carts): void
    {
        $list = $wishlist->current(create: false);
        $item = $list?->items()->with('product')->find($itemId);

        if (! $item?->product) {
            return;
        }

        try {
            $carts->add($item->product, $item->variant, 1);
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $item->delete();

        $this->dispatch('wishlist-updated');
        $this->dispatch('cart-updated');
        $this->toastSuccess(__('hanbell.cart.moved_to_bag'));
    }

    public function render(WishlistService $wishlist): View
    {
        $list = $wishlist->current(create: false);

        return view('livewire.storefront.wishlist', [
            'items' => $list
                ? $list->items()->with(['product.media', 'product.vendor', 'product.inventories', 'variant'])->latest()->get()
                : collect(),
            'seo' => app(Seo::class)->title(__('hanbell.nav.wishlist'))->noindex(),
        ]);
    }
}