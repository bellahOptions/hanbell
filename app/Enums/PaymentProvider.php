<?php

namespace App\Enums;

/**
 * Payment providers. Local (NGN) first, international rails alongside.
 *
 * `card` and `bank` are capability flags used by the checkout UI to decide
 * which method groups to offer; the gateway itself remains the source of truth.
 */
enum PaymentProvider: string
{
    case Paystack = 'paystack';
    case Flutterwave = 'flutterwave';
    case Stripe = 'stripe';
    case PayPal = 'paypal';
    case BankTransfer = 'bank_transfer';
    case CashOnDelivery = 'cash_on_delivery';

    public function label(): string
    {
        return match ($this) {
            self::Paystack => 'Paystack',
            self::Flutterwave => 'Flutterwave',
            self::Stripe => 'Stripe',
            self::PayPal => 'PayPal',
            self::BankTransfer => 'Bank transfer',
            self::CashOnDelivery => 'Pay on delivery',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Paystack => 'Cards, bank transfer and USSD — instant Naira checkout.',
            self::Flutterwave => 'Cards, mobile money and bank accounts across Africa.',
            self::Stripe => 'International cards, Apple Pay and Google Pay.',
            self::PayPal => 'Pay with your PayPal balance or linked card.',
            self::BankTransfer => 'Transfer to a Hanbell account and upload proof of payment.',
            self::CashOnDelivery => 'Pay the courier when your order arrives. Lagos & Abuja only.',
        };
    }

    /** Currencies this rail can settle. Keys are ISO-4217 codes. */
    public function currencies(): array
    {
        return match ($this) {
            self::Paystack => ['NGN'],
            self::Flutterwave => ['NGN', 'USD', 'GBP', 'EUR', 'GHS', 'KES', 'ZAR'],
            self::Stripe => ['USD', 'GBP', 'EUR', 'CAD', 'AUD'],
            self::PayPal => ['USD', 'GBP', 'EUR', 'CAD', 'AUD'],
            self::BankTransfer, self::CashOnDelivery => ['NGN'],
        };
    }

    /** Whether the rail is a hosted redirect flow (vs. offline instructions). */
    public function isOnline(): bool
    {
        return in_array($this, [self::Paystack, self::Flutterwave, self::Stripe, self::PayPal], true);
    }

    public function supportsCurrency(string $currency): bool
    {
        return in_array(strtoupper($currency), $this->currencies(), true);
    }

    /** Config key holding the secret for this provider, if any. */
    public function secretConfigKey(): ?string
    {
        return match ($this) {
            self::Paystack => 'payments.paystack.secret_key',
            self::Flutterwave => 'payments.flutterwave.secret_key',
            self::Stripe => 'payments.stripe.secret_key',
            self::PayPal => 'payments.paypal.client_secret',
            self::BankTransfer, self::CashOnDelivery => null,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
