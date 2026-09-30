<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\PaymentProvider;
use App\Payments\PaymentGatewayManager;
use App\Services\Commerce\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Payment gateway webhooks.
 *
 * Three rules, in order:
 *
 *  1. Verify the signature against the raw body. An unsigned or mis-signed
 *     request is rejected outright — without this, anyone could POST "paid" and
 *     get free goods.
 *
 *  2. De-duplicate on (provider, event id). Gateways retry aggressively; a
 *     replayed event must not deduct stock or send a second confirmation.
 *
 *  3. Re-verify with the provider's API before changing anything, and check the
 *     settled amount and currency. The payload itself is never trusted.
 *
 * Always responds 200 once the event has been recorded, even if verification
 * says the payment failed: a non-2xx makes the gateway retry forever for an
 * event that will never succeed.
 */
class PaymentWebhookController
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentService $payments,
    ) {}

    public function __invoke(Request $request, string $provider): JsonResponse
    {
        $providerEnum = PaymentProvider::tryFrom($provider);

        if (! $providerEnum) {
            return response()->json(['message' => 'Unknown provider.'], 404);
        }

        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true) ?: [];

        $gateway = $this->gateways->gateway($providerEnum);

        $signature = $this->signatureFrom($request, $providerEnum);

        if (! $gateway->verifyWebhookSignature($rawBody, $payload, $signature)) {
            Log::warning('Rejected a payment webhook with an invalid signature.', [
                'provider' => $provider,
                'ip' => $request->ip(),
            ]);

            // 401, not 200: this is not a legitimate delivery and should not be
            // acknowledged as processed.
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $eventId = $gateway->extractWebhookEventId($payload);

        ['event' => $event, 'duplicate' => $duplicate] = $this->payments->recordWebhook(
            $providerEnum,
            $payload,
            $eventId,
        );

        if ($duplicate) {
            return response()->json([
                'status' => 'already processed',
                'event' => $event->event_id,
            ]);
        }

        $reference = $gateway->extractWebhookReference($payload);

        $payment = $reference
            ? $this->payments->resolveFromCallback($providerEnum, $reference)
            : null;

        if (! $payment) {
            Log::warning('Payment webhook referenced a payment we do not have.', [
                'provider' => $provider,
                'reference' => $reference,
                'event' => $eventId,
            ]);

            // Recorded so the id is not reprocessed forever, but nothing to do.
            $event->markProcessed();

            return response()->json(['status' => 'no matching payment']);
        }

        $verified = $this->payments->verifyAndComplete($payment);

        $event->markProcessed();

        return response()->json([
            'status' => $verified ? 'completed' : 'not completed',
            'order' => $payment->order?->number,
        ]);
    }

    /**
     * Pull the signature header for each provider.
     * Paystack and Stripe use a header; Flutterwave uses `verif-hash`.
     */
    private function signatureFrom(Request $request, PaymentProvider $provider): ?string
    {
        return match ($provider) {
            PaymentProvider::Paystack => $request->header('x-paystack-signature'),
            PaymentProvider::Flutterwave => $request->header('verif-hash'),
            PaymentProvider::Stripe => $request->header('stripe-signature'),
            PaymentProvider::PayPal => $request->header('paypal-transmission-sig'),
            default => null,
        };
    }
}
