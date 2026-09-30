<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentProvider;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\DataTransferObjects\PaymentInitialization;
use App\Payments\DataTransferObjects\PaymentVerification;
use App\Payments\Exceptions\PaymentFailedException;
use App\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use App\Support\Money;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Stripe — international card rail (Checkout Sessions).
 *
 * Uses form-encoded requests, which is what Stripe's REST API expects; the
 * bracketed keys below are Stripe's own nested-parameter syntax, not a typo.
 */
class StripeGateway implements PaymentGateway
{
    public function provider(): PaymentProvider
    {
        return PaymentProvider::Stripe;
    }

    public function label(): string
    {
        return (string) config('payments.stripe.label', 'Stripe');
    }

    public function isConfigured(): bool
    {
        return filled(config('payments.stripe.secret_key'));
    }

    public function supports(string $currency): bool
    {
        return in_array(
            strtoupper($currency),
            config('payments.stripe.currencies', ['USD', 'GBP', 'EUR']),
            true,
        );
    }

    public function initialize(Order $order, Payment $payment): PaymentInitialization
    {
        $this->guardConfigured();

        $currency = strtolower($payment->currency);

        $response = $this->client()->post('/checkout/sessions', [
            'mode' => 'payment',
            'success_url' => route('checkout.callback', ['provider' => $this->provider()->value]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel', ['provider' => $this->provider()->value]),
            'client_reference_id' => $payment->reference,
            'customer_email' => $order->email,
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => $currency,
            // Stripe wants the smallest currency unit — same as our storage.
            'line_items[0][price_data][unit_amount]' => $payment->amount_minor,
            'line_items[0][price_data][product_data][name]' => 'Order '.$order->number,
            'line_items[0][price_data][product_data][description]' => config('hanbell.name').' purchase',
            'metadata[order_number]' => $order->number,
            'metadata[order_uuid]' => $order->uuid,
            'payment_intent_data[metadata][order_number]' => $order->number,
        ]);

        $body = $response->json() ?? [];

        if (! $response->successful()) {
            throw new PaymentFailedException(
                $body['error']['message'] ?? 'Stripe could not start this payment.',
                ['status' => $response->status(), 'body' => $body],
            );
        }

        return PaymentInitialization::redirect(
            (string) ($body['url'] ?? ''),
            isset($body['id']) ? (string) $body['id'] : null,
            $body,
        );
    }

    public function verify(Payment $payment): PaymentVerification
    {
        $this->guardConfigured();

        // Verify by the Checkout Session we created, falling back to the
        // PaymentIntent if only that reference was stored.
        $reference = $payment->provider_reference;

        if (blank($reference)) {
            return PaymentVerification::failed('This payment has no Stripe session to verify.');
        }

        $endpoint = str_starts_with($reference, 'cs_')
            ? '/checkout/sessions/'.rawurlencode($reference)
            : '/payment_intents/'.rawurlencode($reference);

        $response = $this->client()->get($endpoint, str_starts_with($reference, 'cs_')
            ? ['expand[]' => 'payment_intent']
            : []);

        $body = $response->json() ?? [];

        if (! $response->successful()) {
            return PaymentVerification::failed(
                $body['error']['message'] ?? 'Could not verify this payment with Stripe.',
                $body,
            );
        }

        // A Checkout Session reports payment_status; a PaymentIntent reports status.
        $successful = ($body['payment_status'] ?? null) === 'paid'
            || ($body['status'] ?? null) === 'succeeded';

        $intent = $body['payment_intent'] ?? null;

        $amountMinor = (int) ($body['amount_total']
            ?? (is_array($intent) ? ($intent['amount_received'] ?? $intent['amount'] ?? 0) : 0));

        $currency = strtoupper((string) ($body['currency']
            ?? (is_array($intent) ? ($intent['currency'] ?? $payment->currency) : $payment->currency)));

        return new PaymentVerification(
            successful: $successful,
            amountMinor: $amountMinor,
            currency: $currency,
            providerReference: isset($body['id']) ? (string) $body['id'] : null,
            channel: is_array($intent) ? ($intent['payment_method_types'][0] ?? 'card') : 'card',
            failureReason: $successful ? null : 'Stripe reports this payment as unpaid.',
            payload: $body,
        );
    }

    public function refund(Payment $payment, int $amountMinor, ?string $reason = null): PaymentVerification
    {
        $this->guardConfigured();

        $intentId = $payment->provider_reference;

        if (blank($intentId)) {
            return PaymentVerification::failed('This payment has no Stripe reference to refund against.');
        }

        $response = $this->client()->post('/refunds', array_filter([
            'payment_intent' => $intentId,
            'amount' => $amountMinor,
            'reason' => $reason === 'requested_by_customer' ? $reason : null,
        ]));

        $body = $response->json() ?? [];

        if (! $response->successful()) {
            return PaymentVerification::failed(
                $body['error']['message'] ?? 'Stripe refused this refund.',
                $body,
            );
        }

        return new PaymentVerification(
            successful: ($body['status'] ?? null) !== 'failed',
            amountMinor: (int) ($body['amount'] ?? $amountMinor),
            currency: strtoupper((string) ($body['currency'] ?? $payment->currency)),
            providerReference: $payment->provider_reference,
            payload: $body,
        );
    }

    /**
     * Stripe signs `{timestamp}.{raw body}` with HMAC-SHA256 and sends the
     * result in the `Stripe-Signature` header alongside the timestamp. The
     * timestamp must also be checked, otherwise a captured webhook stays valid
     * forever (a replay attack).
     */
    public function verifyWebhookSignature(string $rawBody, array $payload, ?string $signatureHeader): bool
    {
        $secret = (string) config('payments.stripe.webhook_secret');

        if ($secret === '' || blank($signatureHeader)) {
            return false;
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', (string) $signatureHeader) as $part) {
            $bits = explode('=', trim($part), 2);

            if (count($bits) !== 2) {
                continue;
            }

            [$key, $value] = $bits;

            if ($key === 't') {
                $timestamp = $value;
            }

            if ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || $signatures === []) {
            return false;
        }

        $tolerance = (int) config('payments.webhook_tolerance_seconds', 300);

        if ($tolerance > 0 && abs(time() - (int) $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    public function extractWebhookEventId(array $payload): ?string
    {
        $id = $payload['id'] ?? null;

        return $id === null ? null : (string) $id;
    }

    public function extractWebhookReference(array $payload): ?string
    {
        $object = $payload['data']['object'] ?? [];

        $reference = $object['client_reference_id']
            ?? $object['metadata']['order_reference']
            ?? null;

        return $reference === null ? null : (string) $reference;
    }

    /* ------------------------------------------------------------------ */

    private function client(): PendingRequest
    {
        // Stripe expects application/x-www-form-urlencoded, not JSON.
        return Http::baseUrl((string) config('payments.stripe.base_url'))
            ->withToken((string) config('payments.stripe.secret_key'))
            ->acceptJson()
            ->asForm()
            ->timeout(30)
            ->retry(2, 250, throw: false);
    }

    private function guardConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new PaymentGatewayNotConfiguredException($this->label());
        }
    }

    /** Convenience for the checkout summary when quoting in a foreign currency. */
    public function displayAmount(int $minor, string $currency): string
    {
        return Money::format($minor, $currency);
    }
}
