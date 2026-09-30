<?php

namespace App\Livewire\Account;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\WishlistItem;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Wishlist extends Component
{
    use InteractsWithToasts;

    public function remove(int $itemId): void
    {
        // Scoped through the user's own wishlist, so a client-supplied id cannot
        // delete another account's saved item.
        WishlistItem::whereHas('wishlist', fn ($q) => $q->where('user_id', auth()->id()))
            ->find($itemId)
            ?->delete();

        $this->dispatch('wishlist-updated');
        $this->toastSuccess(__('hanbell.product.remove_from_wishlist'));
    }

    public function moveToBag(int $itemId, \App\Services\Commerce\CartService $carts): void
    {
        $item = WishlistItem::whereHas('wishlist', fn ($q) => $q->where('user_id', auth()->id()))
            ->with('product')
            ->find($itemId);

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

    public function render(): View
    {
        return view('livewire.account.wishlist', [
            'items' => WishlistItem::whereHas('wishlist', fn ($q) => $q->where('user_id', auth()->id()))
                ->with(['product.media', 'product.vendor', 'product.inventories'])
                ->latest()
                ->get(),
            'seo' => app(Seo::class)->title(__('hanbell.nav.wishlist'))->noindex(),
        ]);
    }
}