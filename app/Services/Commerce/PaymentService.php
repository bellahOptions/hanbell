<?php

namespace App\Services\Commerce;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\DataTransferObjects\PaymentInitialization;
use App\Payments\Exceptions\PaymentFailedException;
use App\Payments\PaymentGatewayManager;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Payment lifecycle.
 *
 * The one rule that matters here: a payment is never treated as successful
 * because a browser came back with a success flag or because a webhook said so.
 * `verifyAndComplete()` always re-verifies against the provider's own API and
 * checks the settled amount and currency before an order is marked paid.
 *
 * It is also idempotent — the browser callback and the webhook both call it, and
 * whichever arrives second is a no-op.
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly OrderService $orders,
    ) {}

    /**
     * Create a pending payment and ask the gateway where to send the shopper.
     */
    public function initiate(Order $order, PaymentProvider $provider): PaymentInitialization
    {
        $gateway = $this->gateways->gateway($provider);

        if (! $gateway->isConfigured()) {
            throw new PaymentFailedException(
                sprintf('%s is not configured yet, so it cannot accept a payment.', $gateway->label())
            );
        }

        if (! $gateway->supports($order->currency)) {
            throw new PaymentFailedException(
                sprintf('%s cannot settle %s.', $gateway->label(), $order->currency)
            );
        }

        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => $provider,
            'status' => PaymentStatus::Pending,
            'reference' => Payment::generateReference(),
            'currency' => $order->currency,
            'amount_minor' => $order->total_minor,
            'initialized_at' => now(),
        ]);

        try {
            $initialization = $gateway->initialize($order, $payment);

            $payment->forceFill([
                'provider_reference' => $initialization->providerReference,
                'status' => PaymentStatus::Processing,
                'gateway_payload' => $initialization->payload ?: null,
            ])->save();

            return $initialization;
        } catch (Throwable $e) {
            // The payment row is kept in a failed state so the attempt is
            // auditable rather than vanishing.
            $payment->markFailed($e->getMessage(), ['exception' => $e::class]);

            throw $e;
        }
    }

    /**
     * Re-verify a payment with the provider and, only if it genuinely succeeded
     * for the exact expected amount and currency, mark the order paid.
     *
     * Safe to call repeatedly; the first successful call wins.
     */
    public function verifyAndComplete(Payment $payment): bool
    {
        $payment->refresh();
        $order = $payment->order;

        if (! $order) {
            Log::warning('Payment has no order; cannot complete.', ['payment' => $payment->reference]);

            return false;
        }

        // Already settled — nothing to do, and no second email.
        if ($payment->status === PaymentStatus::Succeeded && $order->paid_at !== null) {
            return true;
        }

        $gateway = $this->gateways->gateway($payment->provider);

        try {
            $verification = $gateway->verify($payment);
        } catch (Throwable $e) {
            Log::error('Payment verification failed against the provider API.', [
                'payment' => $payment->reference,
                'provider' => $payment->provider instanceof PaymentProvider ? $payment->provider->value : $payment->provider,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        // The provider had no credentials or could not be reached: leave the
        // payment pending rather than guessing an outcome.
        if (! $verification->successful) {
            $payment->markFailed(
                $verification->failureReason ?? 'Payment was not successful.',
                $verification->payload,
            );

            return false;
        }

        // Amount and currency must match what we asked for. Without this check a
        // provider that reports a smaller settled amount would still release
        // the goods.
        if (! $verification->matches($payment->amount_minor, $payment->currency)) {
            $reason = $verification->mismatchReason($payment->amount_minor, $payment->currency);

            Log::critical('Payment amount/currency mismatch — order left unpaid.', [
                'payment' => $payment->reference,
                'order' => $order->number,
                'expected_minor' => $payment->amount_minor,
                'reported_minor' => $verification->amountMinor,
                'expected_currency' => $payment->currency,
                'reported_currency' => $verification->currency,
            ]);

            $payment->markFailed($reason, $verification->payload);

            return false;
        }

        return DB::transaction(function () use ($payment, $order, $verification): bool {
            $payment->forceFill([
                'status' => PaymentStatus::Succeeded,
                'provider_reference' => $verification->providerReference ?: $payment->provider_reference,
                'channel' => $verification->channel,
                'paid_at' => $payment->paid_at ?? now(),
                'failure_reason' => null,
                'gateway_payload' => $verification->payload ?: $payment->gateway_payload,
            ])->save();

            // markPaid is itself idempotent, so the browser callback and the
            // webhook racing each other is harmless.
            $this->orders->markPaid($order, $verification->providerReference);

            return true;
        });
    }

    /**
     * Find the payment behind a provider callback.
     *
     * Gateways hand back different identifiers (Paystack: our reference;
     * Flutterwave: either our tx_ref or their numeric id; Stripe: a session id),
     * so every known field is tried before giving up.
     */
    public function resolveFromCallback(PaymentProvider $provider, ?string $reference, ?string $providerReference = null): ?Payment
    {
        return Payment::query()
            ->where('provider', $provider)
            ->where(function ($query) use ($reference, $providerReference): void {
                if (filled($reference)) {
                    $query->where('reference', $reference);
                }

                if (filled($providerReference)) {
                    $query->orWhere('provider_reference', $providerReference);
                }
            })
            ->latest()
            ->first();
    }

    /**
     * Record and de-duplicate an inbound webhook.
     *
     * Returns the event row plus whether it had already been processed. The
     * unique (provider, event_id) index makes a replay a no-op rather than a
     * second stock deduction.
     *
     * @param  array<string,mixed>  $payload
     * @return array{event:PaymentWebhookEvent, duplicate:bool}
     */
    public function recordWebhook(PaymentProvider $provider, array $payload, ?string $eventId): array
    {
        $eventId = $eventId ?: hash('sha256', json_encode($payload));

        $existing = PaymentWebhookEvent::where('provider', $provider->value)
            ->where('event_id', $eventId)
            ->first();

        if ($existing) {
            return ['event' => $existing, 'duplicate' => true];
        }

        try {
            $event = PaymentWebhookEvent::create([
                'provider' => $provider->value,
                'event_id' => $eventId,
                'event_type' => (string) ($payload['event'] ?? $payload['type'] ?? $payload['event_type'] ?? 'unknown'),
                'payload' => $payload,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // A concurrent delivery won the race; treat as a duplicate.
            $event = PaymentWebhookEvent::where('provider', $provider->value)
                ->where('event_id', $eventId)
                ->firstOrFail();

            return ['event' => $event, 'duplicate' => true];
        }

        return ['event' => $event, 'duplicate' => false];
    }

    /**
     * Refund a successful payment (fully by default).
     */
    public function refund(Payment $payment, ?int $amountMinor = null, ?string $reason = null): bool
    {
        if (! $payment->isSuccessful()) {
            throw new PaymentFailedException('Only a successful payment can be refunded.');
        }

        $amount = $amountMinor ?? $payment->refundableMinor();

        if ($amount <= 0) {
            throw new PaymentFailedException('There is nothing left to refund on this payment.');
        }

        if ($amount > $payment->refundableMinor()) {
            throw new PaymentFailedException('The refund exceeds the amount still refundable.');
        }

        $gateway = $this->gateways->gateway($payment->provider);
        $result = $gateway->refund($payment, $amount, $reason);

        if (! $result->successful) {
            throw new PaymentFailedException($result->failureReason ?? 'The provider refused this refund.');
        }

        $refunded = $payment->refunded_minor + $amount;

        $payment->forceFill([
            'refunded_minor' => $refunded,
            'status' => $refunded >= $payment->amount_minor
                ? PaymentStatus::Refunded
                : PaymentStatus::PartiallyRefunded,
        ])->save();

        if ($refunded >= $payment->amount_minor && $payment->order) {
            $this->orders->refund($payment->order, $reason);
        }

        return true;
    }

    /**
     * Amounts and labels for the checkout page, limited to usable rails.
     *
     * @return array<int,array{provider:string,label:string,description:string,online:bool}>
     */
    public function availableMethods(string $currency): array
    {
        return $this->gateways->availableFor($currency)
            ->map(fn (PaymentGateway $gateway) => [
                'provider' => $gateway->provider()->value,
                'label' => $gateway->label(),
                'description' => $gateway->provider()->description(),
                'online' => $gateway->provider()->isOnline(),
            ])
            ->values()
            ->all();
    }

    public function formattedAmount(Order $order): string
    {
        return Money::format($order->total_minor, $order->currency);
    }
}
