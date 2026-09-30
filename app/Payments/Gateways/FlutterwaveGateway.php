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
 * Flutterwave — the multi-currency African rail.
 *
 * Unlike Paystack, Flutterwave expects the amount in major units (a decimal
 * number), so the conversion goes through Money::toGatewayAmount() rather than
 * passing minor units straight through.
 */
class FlutterwaveGateway implements PaymentGateway
{
    public function provider(): PaymentProvider
    {
        return PaymentProvider::Flutterwave;
    }

    public function label(): string
    {
        return (string) config('payments.flutterwave.label', 'Flutterwave');
    }

    public function isConfigured(): bool
    {
        return filled(config('payments.flutterwave.secret_key'));
    }

    public function supports(string $currency): bool
    {
        return in_array(
            strtoupper($currency),
            config('payments.flutterwave.currencies', ['NGN', 'USD', 'GBP', 'EUR']),
            true,
        );
    }

    public function initialize(Order $order, Payment $payment): PaymentInitialization
    {
        $this->guardConfigured();

        $response = $this->client()->post('/payments', [
            'tx_ref' => $payment->reference,
            'amount' => Money::toGatewayAmount($payment->amount_minor, $payment->currency),
            'currency' => $payment->currency,
            'redirect_url' => route('checkout.callback', ['provider' => $this->provider()->value]),
            'customer' => [
                'email' => $order->email,
                'phonenumber' => $order->phone,
                'name' => $order->customer_name,
            ],
            'customizations' => [
                'title' => config('hanbell.name'),
                'description' => 'Order '.$order->number,
                'logo' => asset('images/logo.svg'),
            ],
            'meta' => [
                'order_number' => $order->number,
                'order_uuid' => $order->uuid,
            ],
        ]);

        $body = $response->json() ?? [];

        if (! $response->successful() || ($body['status'] ?? null) !== 'success') {
            throw new PaymentFailedException(
                $body['message'] ?? 'Flutterwave could not start this payment.',
                ['status' => $response->status(), 'body' => $body],
            );
        }

        $data = $body['data'] ?? [];

        return PaymentInitialization::redirect(
            (string) ($data['link'] ?? ''),
            $payment->reference,
            $body,
        );
    }

    public function verify(Payment $payment): PaymentVerification
    {
        $this->guardConfigured();

        // Flutterwave verifies by our own tx_ref (or by their numeric id).
        $identifier = $payment->reference;
        $query = ctype_digit((string) $payment->provider_reference)
            ? ['id' => $payment->provider_reference]
            : [];

        $response = $query === []
            ? $this->client()->get('/transactions/verify_by_reference', ['tx_ref' => $identifier])
            : $this->client()->get('/transactions/'.rawurlencode((string) $payment->provider_reference).'/verify');

        $body = $response->json() ?? [];

        if (! $response->successful() || ($body['status'] ?? null) !== 'success') {
            return PaymentVerification::failed(
                $body['message'] ?? 'Could not verify this payment with Flutterwave.',
                $body,
            );
        }

        $data = $body['data'] ?? [];
        $successful = in_array(strtolower((string) ($data['status'] ?? '')), ['successful', 'success'], true);

        // Flutterwave reports the amount in major units; convert back.
        $currency = strtoupper((string) ($data['currency'] ?? $payment->currency));
        $amountMinor = Money::toMinor((float) ($data['amount'] ?? 0), $currency);

        return new PaymentVerification(
            successful: $successful,
            amountMinor: $amountMinor,
            currency: $currency,
            providerReference: isset($data['id']) ? (string) $data['id'] : null,
            channel: isset($data['payment_type']) ? (string) $data['payment_type'] : null,
            failureReason: $successful ? null : (string) ($data['processor_response'] ?? 'Payment was not successful.'),
            payload: $body,
        );
    }

    public function refund(Payment $payment, int $amountMinor, ?string $reason = null): PaymentVerification
    {
        $this->guardConfigured();

        if (blank($payment->provider_reference)) {
            return PaymentVerification::failed('This payment has no Flutterwave transaction id to refund against.');
        }

        $response = $this->client()->post(
            '/transactions/'.rawurlencode((string) $payment->provider_reference).'/refund',
            array_filter([
                'amount' => Money::toGatewayAmount($amountMinor, $payment->currency),
                'comments' => $reason,
            ]),
        );

        $body = $response->json() ?? [];

        if (! $response->successful() || ($body['status'] ?? null) !== 'success') {
            return PaymentVerification::failed($body['message'] ?? 'Flutterwave refused this refund.', $body);
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
     * Flutterwave authenticates webhooks with a shared secret hash sent in the
     * `verif-hash` header — not an HMAC over the body. A plain timing-safe
     * comparison is therefore the correct check.
     */
    public function verifyWebhookSignature(string $rawBody, array $payload, ?string $signatureHeader): bool
    {
        $expected = (string) config('payments.flutterwave.webhook_secret');

        if ($expected === '' || blank($signatureHeader)) {
            return false;
        }

        return hash_equals($expected, (string) $signatureHeader);
    }

    public function extractWebhookEventId(array $payload): ?string
    {
        $data = $payload['data'] ?? [];
        $id = $data['id'] ?? null;

        if ($id !== null) {
            return (string) $id;
        }

        $reference = $data['tx_ref'] ?? null;

        return $reference === null ? null : 'tx_ref:'.$reference;
    }

    public function extractWebhookReference(array $payload): ?string
    {
        $data = $payload['data'] ?? [];
        $reference = $data['tx_ref'] ?? null;

        return $reference === null ? null : (string) $reference;
    }

    /* ------------------------------------------------------------------ */

    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('payments.flutterwave.base_url'))
            ->withToken((string) config('payments.flutterwave.secret_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->retry(2, 250, throw: false);
    }

    private function guardConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new PaymentGatewayNotConfiguredException($this->label());
        }
    }
}
