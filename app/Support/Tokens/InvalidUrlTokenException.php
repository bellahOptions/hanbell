<?php

namespace App\Support\Tokens;

use RuntimeException;

/**
 * Thrown when a public URL token cannot be trusted: bad signature, wrong model
 * type, malformed payload, or past its expiry.
 *
 * Route binding catches this and converts it into a 404, so a tampered link
 * looks exactly like a missing page rather than leaking why it failed.
 */
class InvalidUrlTokenException extends RuntimeException
{
    public function __construct(string $message, public readonly string $reason = 'invalid')
    {
        parent::__construct($message);
    }

    public static function malformed(): self
    {
        return new self('This link is not valid.', 'malformed');
    }

    public static function badSignature(): self
    {
        return new self('This link has been altered and can no longer be trusted.', 'signature');
    }

    public static function expired(): self
    {
        return new self('This link has expired.', 'expired');
    }

    public static function wrongType(string $expected, string $actual): self
    {
        return new self("Expected a {$expected} link but received a {$actual} link.", 'type');
    }

    public static function revoked(): self
    {
        return new self('This link has been revoked.', 'revoked');
    }
}
