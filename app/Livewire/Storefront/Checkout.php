<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Address;
use App\Models\Coupon;
use App\Models\Order;
use App\Payments\Exceptions\PaymentFailedException;
use App\Payments\PaymentGatewayManager;
use App\Services\Commerce\CartService;
use App\Services\Commerce\OrderService;
use App\Services\Commerce\PaymentService;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Checkout.
 *
 * The order summary is always computed server-side from the live cart — the
 * browser never supplies a price, a shipping amount or a total. Payment is
 * initiated by redirecting to the gateway's own hosted page; nothing about the
 * order is treated as settled until PaymentService re-verifies it.
 *
 * Administrators are excluded by the `customer` middleware and again in mount().
 */
#[Layout('layouts.app')]
class Checkout extends Component
{
    use InteractsWithToasts;

    /* Contact ---------------------------------------------------------- */
    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string|max:32')]
    public string $phone = '';

    /* Delivery --------------------------------------------------------- */
    #[Validate('required|string|max:120')]
    public string $recipient_name = '';

    #[Validate('required|string|max:32')]
    public string $shipping_phone = '';

    #[Validate('required|string|max:180')]
    public string $line1 = '';

    #[Validate('nullable|string|max:180')]
    public string $line2 = '';

    #[Validate('required|string|max:80')]
    public string $city = '';

    #[Validate('required|string|max:80')]
    public string $state = '';

    #[Validate('nullable|string|max:16')]
    public string $postal_code = '';

    #[Validate('required|string|size:2')]
    public string $country = 'NG';

    /* Preferences ------------------------------------------------------ */
    #[Validate('nullable|string|max:1000')]
    public string $notes = '';

    #[Validate('required|string')]
    public string $payment_provider = '';

    public ?int $selectedAddressId = null;

    public bool $useNewAddress = true;

    public bool $saveAddress = true;

    public function mount(CartService $carts): void
    {
        if (auth()->user()?->isAdmin()) {
            $this->redirect(route('admin.dashboard'));

            return;
        }

        // An empty bag has nothing to check out.
        if ($carts->summary()['line_count'] === 0) {
            $this->redirect(route('storefront.cart'));

            return;
        }

        $user = auth()->user();

        if ($user) {
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = (string) $user->phone;
            $this->recipient_name = $user->name;

            $default = $user->addresses()->where('is_default', true)->first()
                ?? $user->addresses()->first();

            if ($default) {
                $this->fillFromAddress($default);
                $this->selectedAddressId = $default->id;
                $this->useNewAddress = false;
            }
        }

        // Pre-select the first usable rail so the form is complete on arrival.
        $this->payment_provider = array_key_first(
            app(PaymentGatewayManager::class)->optionsFor($this->currency())
        ) ?? '';
    }

    public function selectAddress(int $addressId): void
    {
        $address = Address::where('user_id', auth()->id())->find($addressId);

        if (! $address) {
            return;
        }

        $this->fillFromAddress($address);
        $this->selectedAddressId = $addressId;
        $this->useNewAddress = false;
    }

    public function useNewAddressForm(): void
    {
        $this->useNewAddress = true;
        $this->selectedAddressId = null;
        $this->reset(['line1', 'line2', 'city', 'state', 'postal_code']);
        $this->country = 'NG';
    }

    private function fillFromAddress(Address $address): void
    {
        $this->recipient_name = $address->recipient_name;
        $this->shipping_phone = $address->phone;
        $this->line1 = $address->line1;
        $this->line2 = (string) $address->line2;
        $this->city = $address->city;
        $this->state = $address->state;
        $this->postal_code = (string) $address->postal_code;
        $this->country = $address->country;
    }

    public function placeOrder(
        CartService $carts,
        OrderService $orders,
        PaymentService $payments,
    ) {
        $this->validate();

        $cart = $carts->current(create: false);

        if (! $cart || $cart->isEmpty()) {
            $this->toastError(__('hanbell.cart.empty'));

            return null;
        }

        // Re-validate that the chosen rail can actually settle this currency.
        $manager = app(PaymentGatewayManager::class);

        if (! $manager->isAvailable($this->payment_provider, $this->currency())) {
            $this->toastError(__('hanbell.checkout.payment_unavailable', ['currency' => $this->currency()]));

            return null;
        }

        $coupon = Session::get('hanbell.coupon')
            ? Coupon::where('code', Session::get('hanbell.coupon'))->first()
            : null;

        try {
            $order = $orders->createFromCart(
                cart: $cart,
                shipping: [
                    'recipient_name' => $this->recipient_name,
                    'phone' => $this->shipping_phone,
                    'line1' => $this->line1,
                    'line2' => $this->line2,
                    'city' => $this->city,
                    'state' => $this->state,
                    'postal_code' => $this->postal_code,
                    'country' => $this->country,
                ],
                contact: [
                    'name' => $this->name,
                    'email' => $this->email,
                    'phone' => $this->phone,
                ],
                coupon: $coupon,
                notes: $this->notes,
                user: auth()->user(),
            );
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return null;
        }

        if ($this->saveAddress && auth()->check() && $this->useNewAddress) {
            auth()->user()->addresses()->create([
                'recipient_name' => $this->recipient_name,
                'phone' => $this->shipping_phone,
                'line1' => $this->line1,
                'line2' => $this->line2,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
            ]);
        }

        Session::forget('hanbell.coupon');
        $this->dispatch('cart-updated');

        // Hand off to the gateway. The order stays `pending` until the callback
        // or webhook re-verifies the payment.
        try {
            $initialization = $payments->initiate(
                $order,
                \App\Enums\PaymentProvider::from($this->payment_provider),
            );
        } catch (PaymentFailedException $e) {
            $this->toastError($e->getMessage());

            return $this->redirect(route('storefront.orders.show', ['order' => $order->urlToken()]));
        }

        if ($initialization->requiresRedirect()) {
            return $this->redirect($initialization->redirectUrl);
        }

        // An offline rail (bank transfer, pay on delivery): show the
        // instructions on the order page instead of redirecting anywhere.
        $this->toastSuccess(__('hanbell.checkout.order_placed'), $order->number);

        return $this->redirect(route('storefront.orders.placed', ['order' => $order->urlToken()]));
    }

    private function currency(): string
    {
        return (string) (auth()->user()?->currency ?: config('hanbell.currency.default', 'NGN'));
    }

    public function render(CartService $carts, PaymentService $payments): View
    {
        $cart = $carts->current(create: false);

        $coupon = Session::get('hanbell.coupon')
            ? Coupon::where('code', Session::get('hanbell.coupon'))->first()
            : null;

        $discount = 0;

        if ($coupon && $cart) {
            $discount = $coupon->discountFor($carts->summary($cart)['subtotal_minor']);
        }

        return view('livewire.storefront.checkout', [
            'summary' => $carts->summary($cart, discountMinor: $discount),
            'coupon' => $coupon,
            'discount' => $discount,
            'methods' => $payments->availableMethods($this->currency()),
            'addresses' => auth()->check() ? auth()->user()->addresses()->get() : collect(),
            'cod' => app(\App\Payments\Gateways\CashOnDeliveryGateway::class),
            'seo' => app(Seo::class)->title(__('hanbell.checkout.title'))->noindex(),
        ]);
    }
}
