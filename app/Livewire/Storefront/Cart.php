<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Services\Commerce\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;

#[Layout('layouts.app')]
class Cart extends Component
{
    use InteractsWithToasts;

    /** The applied promo code survives a refresh, so a shopper does not lose it. */
    #[Session]
    public ?string $couponCode = null;

    #[On('cart-updated')]
    public function refresh(): void
    {
        // Intentionally empty: the listener exists so header counts and any
        // other listener re-render in step with a cart mutation.
    }

    public function updateQuantity(int $itemId, int $quantity): void
    {
        $item = CartItem::with('product.variant')->find($itemId);

        if (! $item) {
            return;
        }

        try {
            app(CartService::class)->updateQuantity($item, $quantity);
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $this->dispatch('cart-updated');
        $this->toastSuccess(__('hanbell.cart.quantity_updated'));
    }

    public function remove(int $itemId): void
    {
        $item = CartItem::find($itemId);

        if (! $item) {
            return;
        }

        app(CartService::class)->remove($item);

        $this->dispatch('cart-updated');
        $this->toastSuccess(__('hanbell.cart.item_removed'));
    }

    public function clear(): void
    {
        app(CartService::class)->clear();

        $this->couponCode = null;

        $this->dispatch('cart-updated');
        $this->toastSuccess(__('hanbell.cart.item_removed'));
    }

    public function applyCoupon(): void
    {
        $code = strtoupper(trim((string) $this->couponCode));

        if ($code === '') {
            return;
        }

        $coupon = Coupon::where('code', $code)->first();

        // The basket total decides whether the coupon's minimum is met, so the
        // check happens here rather than at checkout.
        $subtotal = app(CartService::class)->summary()['subtotal_minor'];

        if (! $coupon || ! $coupon->isRedeemable() || $coupon->discountFor($subtotal) <= 0) {
            $this->couponCode = null;
            $this->toastError(__('hanbell.cart.coupon_invalid'));

            return;
        }

        $this->couponCode = $code;
        $this->toastSuccess(__('hanbell.cart.coupon_applied'));
    }

    public function removeCoupon(): void
    {
        $this->couponCode = null;
        $this->toastInfo(__('hanbell.cart.coupon_removed'));
    }

    public function render(CartService $carts): View
    {
        $cart = $carts->current(create: false);
        $coupon = $this->couponCode ? Coupon::where('code', $this->couponCode)->first() : null;

        $discount = 0;

        if ($coupon && $cart) {
            $discount = $coupon->discountFor($carts->summary($cart)['subtotal_minor']);
        }

        $summary = $carts->summary($cart, discountMinor: $discount);

        return view('livewire.storefront.cart', [
            'cart' => $cart,
            'summary' => $summary,
            'coupon' => $coupon,
            'discount' => $discount,
            'untilFreeShipping' => $carts->amountUntilFreeShipping($summary['subtotal_minor']),
            'qualifiesForFreeShipping' => $carts->qualifiesForFreeShipping($summary['subtotal_minor']),
        ]);
    }
}
