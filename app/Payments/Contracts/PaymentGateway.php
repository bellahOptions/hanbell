<?php

namespace App\Payments\Contracts;

use App\Enums\PaymentProvider;
use App\Payments\DataTransferObjects\PaymentInitialization;
use App\Payments\DataTransferObjects\PaymentVerification;
use App\Models\Order;
use App\Models\Payment;

/**
 * A payment rail.
 *
 * Implementations must talk to the provider's real API. There is no mock mode:
 * when credentials are missing, `isConfigured()` returns false and the
 * checkout page hides the rail rather than pretending a payment succeeded.
 */
interface PaymentGateway
{
    /** Which rail this is. */
    public function provider(): PaymentProvider;

    /** Human label for the checkout page. */
    public function label(): string;

    /** True when the credentials needed to transact are present. */
    public function isConfigured(): bool;

    /** Whether this rail can settle the given ISO-4217 currency. */
    public function supports(string $currency): bool;

    /**
     * Create a pending payment with the provider and return where to send the
     * shopper next.
     */
    public function initialize(Order $order, Payment $payment): PaymentInitialization;

    /**
     * Re-verify a payment against the provider's API.
     *
     * This is the only trusted source of truth. Neither the browser callback
     * nor a webhook payload is ever sufficient on its own.
     */
    public function verify(Payment $payment): PaymentVerification;

    /**
     * Refund some or all of a successful payment.
     *
     * @param  int  $amountMinor  amount to refund in minor units
     */
    public function refund(Payment $payment, int $amountMinor, ?string $reason = null): PaymentVerification;

    /**
     * Verify that a webhook genuinely came from the provider.
     *
     * @param  array<string,mixed>  $payload
     */
    public function verifyWebhookSignature(string $rawBody, array $payload, ?string $signatureHeader): bool;

    /**
     * Extract the provider's own event id from a webhook, for deduplication.
     *
     * @param  array<string,mixed>  $payload
     */
    public function extractWebhookEventId(array $payload): ?string;

    /**
     * Pull our payment reference out of a webhook payload.
     *
     * @param  array<string,mixed>  $payload
     */
    public function extractWebhookReference(array $payload): ?string;
}
