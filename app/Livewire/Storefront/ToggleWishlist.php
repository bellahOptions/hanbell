<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Commerce\WishlistService;
use App\Services\Inventory\InsufficientStockException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The heart button on a product tile.
 *
 * Isolated into its own component so toggling a wishlist item does not
 * re-render (and re-query) the whole product grid it sits in.
 */
class ToggleWishlist extends Component
{
    use InteractsWithToasts;

    #[Locked]
    public int $productId;

    /** Null means "the product, no specific variant". */
    #[Locked]
    public ?int $variantId = null;

    public bool $saved = false;

    /** Renders as a filled/outline heart; the compact tile style omits the label. */
    public bool $compact = true;

    public function mount(int $productId, ?int $variantId = null, bool $compact = true): void
    {
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->compact = $compact;

        // Administrators have no wishlist at all.
        if (auth()->user()?->isAdmin()) {
            return;
        }

        $this->saved = $this->resolveProduct() !== null
            && app(WishlistService::class)->has($this->resolveProduct(), $this->resolveVariant());
    }

    public function toggle(WishlistService $wishlist): void
    {
        // Guarded here as well as by the `customer` middleware: an admin can
        // still reach this component from a public product page.
        if (auth()->user()?->isAdmin()) {
            $this->toastInfo(__('hanbell.toast.info'), 'Administrator accounts do not have a wishlist.');

            return;
        }

        $product = $this->resolveProduct();

        if (! $product) {
            $this->toastError(__('hanbell.toast.generic_error'));

            return;
        }

        try {
            $added = $wishlist->toggle($product, $this->resolveVariant());
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $this->saved = $added;
        $this->dispatch('wishlist-updated');

        $this->toastSuccess(
            $added ? __('hanbell.product.add_to_wishlist') : __('hanbell.product.remove_from_wishlist'),
        );
    }

    private function resolveProduct(): ?Product
    {
        return Product::find($this->productId);
    }

    private function resolveVariant(): ?ProductVariant
    {
        return $this->variantId ? ProductVariant::find($this->variantId) : null;
    }

    public function render(): View
    {
        return view('livewire.storefront.toggle-wishlist');
    }
}
