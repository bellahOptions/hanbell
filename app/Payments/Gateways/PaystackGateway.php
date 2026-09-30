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
use Illuminate\Support\Str;

/**
 * Paystack — the primary Naira rail.
 *
 * Amounts are sent in the currency's minor unit (kobo for NGN), which is
 * exactly how this application stores money, so no float conversion happens on
 * the way out.
 */
class PaystackGateway implements PaymentGateway
{
    public function provider(): PaymentProvider
    {
        return PaymentProvider::Paystack;
    }

    public function label(): string
    {
        return (string) config('payments.paystack.label', 'Paystack');
    }

    public function isConfigured(): bool
    {
        return filled(config('payments.paystack.secret_key'));
    }

    public function supports(string $currency): bool
    {
        return in_array(strtoupper($currency), config('payments.paystack.currencies', ['NGN']), true);
    }

    public function initialize(Order $order, Payment $payment): PaymentInitialization
    {
        $this->guardConfigured();

        $response = $this->client()->post('/transaction/initialize', [
            'email' => $order->email,
            'amount' => $payment->amount_minor,
            'currency' => $payment->currency,
            'reference' => $payment->reference,
            'callback_url' => route('checkout.callback', ['provider' => $this->provider()->value]),
            'metadata' => [
                'order_number' => $order->number,
                'order_uuid' => $order->uuid,
                'custom_fields' => [
                    [
                        'display_name' => 'Order',
                        'variable_name' => 'order_number',
                        'value' => $order->number,
                    ],
                ],
            ],
            'channels' => ['card', 'bank', 'ussd', 'bank_transfer', 'qr'],
        ]);

        $body = $response->json() ?? [];

        if (! $response->successful() || ($body['status'] ?? false) !== true) {
            throw new PaymentFailedException(
                $body['message'] ?? 'Paystack could not start this payment.',
                ['status' => $response->status(), 'body' => $body],
            );
        }

        $data = $body['data'] ?? [];

        return PaymentInitialization::redirect(
            (string) ($data['authorization_url'] ?? ''),
            isset($data['reference']) ? (string) $data['reference'] : null,
            $body,
        );
    }

    public function verify(Payment $payment): PaymentVerification
    {
        $this->guardConfigured();

        $reference = $payment->provider_reference ?: $payment->reference;

        $response = $this->client()->get('/transaction/verify/'.rawurlencode($reference));
        $body = $response->json() ?? [];

        if (! $response->successful() || ($body['status'] ?? false) !== true) {
            return PaymentVerification::failed(
                $body['message'] ?? 'Could not verify this payment with Paystack.',
                $body,
            );
        }

        $data = $body['data'] ?? [];
        $successful = ($data['status'] ?? null) === 'success';

        return new PaymentVerification(
            successful: $successful,
            amountMinor: (int) ($data['amount'] ?? 0),
            currency: strtoupper((string) ($data['currency'] ?? $payment->currency)),
            providerReference: isset($data['reference']) ? (string) $data['reference'] : null,
            channel: isset($data['channel']) ? (string) $data['channel'] : null,
            failureReason: $successful ? null : (string) ($data['gateway_response'] ?? 'Payment was not successful.'),
            payload: $body,
        );
    }

    public function refund(Payment $payment, int $amountMinor, ?string $reason = null): PaymentVerification
    {
        $this->guardConfigured();

        $response = $this->client()->post('/refund', array_filter([
            'transaction' => $payment->provider_reference ?: $payment->reference,
            'amount' => $amountMinor,
            'merchant_note' => $reason,
        ]));

        $body = $response->json() ?? [];

        if (! $response->successful() || ($body['status'] ?? false) !== true) {
            return PaymentVerification::failed($body['message'] ?? 'Paystack refused this refund.', $body);
        }

        return new PaymentVerification(
            successful: true,
            amountMinor: $amountMinor,
            currency: $payment->currency,
            providerReference: $payment->provider_reference,
            payload: $body,
        );
    }

    /**
     * Paystack signs the raw request body with HMAC-SHA512 using the secret key.
     * The comparison must be timing-safe.
     */
    public function verifyWebhookSignature(string $rawBody, array $payload, ?string $signatureHeader): bool
    {
        $secret = (string) config('payments.paystack.secret_key');

        if ($secret === '' || blank($signatureHeader)) {
            return false;
        }

        $computed = hash_hmac('sha512', $rawBody, $secret);

        return hash_equals($computed, (string) $signatureHeader);
    }

    public function extractWebhookEventId(array $payload): ?string
    {
        // Paystack does not send a stable event id; the transaction reference
        // plus the event type is unique enough to deduplicate a replay.
        $reference = $payload['data']['reference'] ?? null;
        $event = $payload['event'] ?? null;

        if ($reference === null || $event === null) {
            return null;
        }

        return $event.':'.$reference;
    }

    public function extractWebhookReference(array $payload): ?string
    {
        $reference = $payload['data']['reference'] ?? null;

        return $reference === null ? null : (string) $reference;
    }

    /* ------------------------------------------------------------------ */

    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('payments.paystack.base_url'))
            ->withToken((string) config('payments.paystack.secret_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            // Paystack can transiently 5xx; one retry is safe because every
            // call here is either idempotent or keyed by our own reference.
            ->retry(2, 250, throw: false);
    }

    private function guardConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new PaymentGatewayNotConfiguredException($this->label());
        }
    }

    /** Exposed for the checkout page's display of the expected amount. */
    public function displayAmount(Order $order): string
    {
        return Money::format($order->total_minor, $order->currency);
    }

    /** Paystack references must be unique and reasonably short. */
    public static function normaliseReference(string $reference): string
    {
        return Str::limit($reference, 100, '');
    }
}
