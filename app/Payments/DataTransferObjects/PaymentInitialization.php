<?php

namespace App\Payments\DataTransferObjects;

/**
 * Where to send the shopper after initialising a payment.
 */
final readonly class PaymentInitialization
{
    public function __construct(
        /** Hosted checkout URL, or null for an offline rail. */
        public ?string $redirectUrl,
        /** The provider's own reference for this transaction. */
        public ?string $providerReference = null,
        /** Instructions for an offline rail (bank details, etc.). */
        public ?string $instructions = null,
        /** Raw provider response, stored for support and debugging. */
        public array $payload = [],
    ) {}

    public static function redirect(string $url, ?string $providerReference = null, array $payload = []): self
    {
        return new self($url, $providerReference, null, $payload);
    }

    public static function offline(string $instructions, array $payload = []): self
    {
        return new self(null, null, $instructions, $payload);
    }

    public function requiresRedirect(): bool
    {
        return filled($this->redirectUrl);
    }
}
