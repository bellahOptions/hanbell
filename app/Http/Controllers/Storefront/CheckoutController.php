<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\PaymentProvider;
use App\Payments\PaymentGatewayManager;
use App\Services\Commerce\PaymentService;
use Illuminate\Http\Request;

/**
 * Browser return from a hosted payment page.
 *
 * The query string is treated as a *hint* pointing at which payment to look up,
 * never as proof of anything. The actual decision comes from verifyAndComplete(),
 * which re-checks the provider's API and confirms the settled amount and
 * currency. A shopper who edits `?status=success` in the URL achieves nothing.
 */
class CheckoutController
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PaymentGatewayManager $gateways,
    ) {}

    public function callback(Request $request, string $provider)
    {
        $providerEnum = PaymentProvider::from($provider);

        $payment = $this->resolvePayment($request, $providerEnum);

        if (! $payment) {
            return redirect()
                ->route('storefront.home')
                ->with('error', 'We could not match that payment to an order.');
        }

        $verified = $this->payments->verifyAndComplete($payment);

        $order = $payment->order;

        if (! $order) {
            return redirect()->route('storefront.home');
        }

        if ($verified) {
            return redirect()
                ->route('storefront.orders.placed', ['order' => $order->urlToken()])
                ->with('toast', [
                    'message' => __('hanbell.checkout.payment_successful'),
                    'type' => 'success',
                ]);
        }

        // Verification failed or is still pending. The order page shows the
        // true state, which is what the shopper needs rather than a vague error.
        return redirect()
            ->route('storefront.orders.show', ['order' => $order->urlToken()])
            ->with('toast', [
                'message' => __('hanbell.checkout.payment_failed_hint'),
                'type' => 'warning',
            ]);
    }

    public function cancelled(Request $request, string $provider)
    {
        $providerEnum = PaymentProvider::from($provider);
        $payment = $this->resolvePayment($request, $providerEnum);

        $order = $payment?->order;

        return redirect()
            ->route($order ? 'storefront.orders.show' : 'storefront.cart', $order ? ['order' => $order->urlToken()] : [])
            ->with('toast', [
                'message' => __('hanbell.checkout.payment_failed'),
                'type' => 'warning',
            ]);
    }

    /**
     * Find the payment behind a callback, using whichever identifier the
     * provider returns.
     */
    private function resolvePayment(Request $request, PaymentProvider $provider)
    {
        return match ($provider) {
            // Paystack returns ?reference=<our reference>&trxref=<same>
            PaymentProvider::Paystack => $this->payments->resolveFromCallback(
                $provider,
                $request->query('reference') ?: $request->query('trxref'),
            ),

            // Flutterwave returns ?tx_ref=<our reference>&transaction_id=<theirs>
            PaymentProvider::Flutterwave => $this->payments->resolveFromCallback(
                $provider,
                $request->query('tx_ref'),
                $request->query('transaction_id'),
            ),

            // Stripe Checkout returns ?session_id=cs_...
            PaymentProvider::Stripe => $this->payments->resolveFromCallback(
                $provider,
                $request->query('client_reference_id'),
                $request->query('session_id'),
            ),

            // PayPal returns ?token=<their order id>
            PaymentProvider::PayPal => $this->payments->resolveFromCallback(
                $provider,
                $request->query('client_reference_id'),
                $request->query('token'),
            ),

            default => null,
        };
    }
}
