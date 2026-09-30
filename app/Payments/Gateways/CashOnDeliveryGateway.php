<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentProvider;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\DataTransferObjects\PaymentInitialization;
use App\Payments\DataTransferObjects\PaymentVerification;
use App\Support\Money;

/**
 * Pay on delivery — an offline rail restricted to states where our couriers
 * collect cash. Like bank transfer, it is never auto-verified: a human confirms
 * collection.
 */
class CashOnDeliveryGateway implements PaymentGateway
{
    public function provider(): PaymentProvider
    {
        return PaymentProvider::CashOnDelivery;
    }

    public function label(): string
    {
        return (string) config('payments.cash_on_delivery.label', 'Pay on delivery');
    }

    public function isConfigured(): bool
    {
        return (bool) config('payments.cash_on_delivery.enabled', true);
    }

    public function supports(string $currency): bool
    {
        return strtoupper($currency) === 'NGN';
    }

    /**
     * Whether pay-on-delivery is offered for a destination state. Checked by
     * the checkout page so the option never appears for an unserviceable
     * address.
     */
    public function supportsState(?string $state): bool
    {
        $allowed = (array) config('payments.cash_on_delivery.states', []);

        if ($allowed === [] || blank($state)) {
            return false;
        }

        return in_array(strtolower(trim($state)), array_map(
            fn ($s) => strtolower(trim((string) $s)),
            $allowed,
        ), true);
    }

    public function feeMinor(): int
    {
        return (int) config('payments.cash_on_delivery.fee_minor', 0);
    }

    public function initialize(Order $order, Payment $payment): PaymentInitialization
    {
        $instructions = sprintf(
            'Pay %s in cash to the courier when order %s arrives. Please have the exact amount ready.',
            $order->formattedTotal(),
            $order->number,
        );

        if ($this->feeMinor() > 0) {
            $instructions .= ' A handling fee of '.Money::format($this->feeMinor()).' applies.';
        }

        return PaymentInitialization::offline($instructions, ['rail' => 'cash_on_delivery']);
    }

    public function verify(Payment $payment): PaymentVerification
    {
        return PaymentVerification::failed(
            'Pay-on-delivery orders are confirmed when the courier collects payment.',
            ['rail' => 'cash_on_delivery'],
        );
    }

    public function refund(Payment $payment, int $amountMinor, ?string $reason = null): PaymentVerification
    {
        return PaymentVerification::failed('Pay-on-delivery refunds are issued manually.');
    }

    public function verifyWebhookSignature(string $rawBody, array $payload, ?string $signatureHeader): bool
    {
        return false;
    }

    public function extractWebhookEventId(array $payload): ?string
    {
        return null;
    }

    public function extractWebhookReference(array $payload): ?string
    {
        return null;
    }
}
