<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Commerce\CartService;
use App\Services\Inventory\InsufficientStockException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Add to bag, isolated as its own component.
 *
 * Two reasons it is not inlined into the product form:
 *   - it can render as a one-click button on a product card (no variant
 *     selection) or as part of the full detail form;
 *   - it keeps its own loading state, so the surrounding page does not re-render
 *     while the bag is being updated.
 */
class AddToCart extends Component
{
    use InteractsWithToasts;

    #[Locked]
    public int $productId;

    #[Locked]
    public ?int $variantId = null;

    public int $quantity = 1;

    /** Compact = icon-only round button for product tiles. */
    public bool $compact = false;

    public function mount(int $productId, ?int $variantId = null, int $quantity = 1, bool $compact = false): void
    {
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->quantity = max(1, $quantity);
        $this->compact = $compact;
    }

    public function add(CartService $carts): void
    {
        // Re-checked here as well as in the header and middleware: an admin can
        // still reach a public product page, and must not get a cart.
        if (auth()->user()?->isAdmin()) {
            $this->toastInfo('Administrator accounts do not have a shopping bag.');

            return;
        }

        $product = Product::find($this->productId);

        if (! $product) {
            $this->toastError(__('hanbell.toast.generic_error'));

            return;
        }

        $variant = $this->variantId ? ProductVariant::find($this->variantId) : null;

        try {
            $carts->add($product, $variant, $this->quantity);
        } catch (InsufficientStockException $e) {
            // The exception message already explains exactly what is left, which
            // is more useful than a generic failure.
            $this->toastError($e->getMessage());

            return;
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $this->dispatch('cart-updated');

        $this->toastSuccess(
            __('hanbell.cart.item_added'),
            $product->name,
        );

        // Reset so a second click does not silently add a large quantity.
        $this->quantity = 1;
    }

    public function render(): View
    {
        return view('livewire.storefront.add-to-cart', [
            'product' => Product::find($this->productId),
        ]);
    }
}
