<?php

namespace App\Payments\DataTransferObjects;

/**
 * The outcome of a verification or refund call, as reported by the provider.
 */
final readonly class PaymentVerification
{
    public function __construct(
        public bool $successful,
        /** Amount the provider actually settled, in minor units. */
        public int $amountMinor,
        public string $currency,
        public ?string $providerReference = null,
        public ?string $channel = null,
        public ?string $failureReason = null,
        public array $payload = [],
    ) {}

    public static function failed(string $reason, array $payload = []): self
    {
        return new self(false, 0, 'NGN', null, null, $reason, $payload);
    }

    /**
     * Guard against a provider confirming a different amount or currency than
     * we asked for — a real risk when a client can influence the request.
     */
    public function matches(int $expectedAmountMinor, string $expectedCurrency): bool
    {
        return $this->successful
            && $this->amountMinor === $expectedAmountMinor
            && strtoupper($this->currency) === strtoupper($expectedCurrency);
    }

    public function mismatchReason(int $expectedAmountMinor, string $expectedCurrency): string
    {
        if (! $this->successful) {
            return $this->failureReason ?? 'Payment was not successful.';
        }

        if (strtoupper($this->currency) !== strtoupper($expectedCurrency)) {
            return sprintf(
                'Currency mismatch: expected %s, provider reported %s.',
                $expectedCurrency,
                $this->currency,
            );
        }

        return sprintf(
            'Amount mismatch: expected %s, provider reported %s.',
            number_format($expectedAmountMinor / 100, 2),
            number_format($this->amountMinor / 100, 2),
        );
    }
}
