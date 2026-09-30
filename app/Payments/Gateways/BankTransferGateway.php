<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentProvider;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\DataTransferObjects\PaymentInitialization;
use App\Payments\DataTransferObjects\PaymentVerification;

/**
 * Bank transfer — an offline rail.
 *
 * There is nothing to verify against an API, so `verify()` deliberately reports
 * failure: the order only becomes paid when an administrator confirms the
 * transfer landed. That keeps the "never declare a payment successful without
 * verification" rule intact even for offline money.
 */
class BankTransferGateway implements PaymentGateway
{
    public function provider(): PaymentProvider
    {
        return PaymentProvider::BankTransfer;
    }

    public function label(): string
    {
        return (string) config('payments.bank_transfer.label', 'Bank transfer');
    }

    public function isConfigured(): bool
    {
        return filled(config('payments.bank_transfer.account_number'));
    }

    public function supports(string $currency): bool
    {
        return strtoupper($currency) === 'NGN';
    }

    public function initialize(Order $order, Payment $payment): PaymentInitialization
    {
        $instructions = sprintf(
            "Transfer %s to:\n%s\nAccount name: %s\nAccount number: %s\n\nUse \"%s\" as the transfer narration, then upload your receipt on the order page.",
            $order->formattedTotal(),
            config('payments.bank_transfer.bank_name'),
            config('payments.bank_transfer.account_name'),
            config('payments.bank_transfer.account_number'),
            $order->number,
        );

        return PaymentInitialization::offline($instructions, ['rail' => 'bank_transfer']);
    }

    public function verify(Payment $payment): PaymentVerification
    {
        return PaymentVerification::failed(
            'Bank transfers are confirmed manually by our team, not automatically.',
            ['rail' => 'bank_transfer'],
        );
    }

    public function refund(Payment $payment, int $amountMinor, ?string $reason = null): PaymentVerification
    {
        return PaymentVerification::failed('Bank transfer refunds are processed manually by an administrator.');
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
