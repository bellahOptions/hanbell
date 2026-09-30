<?php

namespace Tests\Unit\Support;

use App\Support\Tokens\InvalidUrlTokenException;
use App\Support\Tokens\UrlToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UrlTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        UrlToken::flushKeys();
    }

    #[Test]
    public function it_round_trips_a_token(): void
    {
        $token = UrlToken::encode('product', 'uuid-123', 3);
        $payload = UrlToken::decode($token, 'product');

        $this->assertSame('product', $payload['type']);
        $this->assertSame('uuid-123', $payload['uuid']);
        $this->assertSame(3, $payload['version']);
    }

    #[Test]
    public function the_token_does_not_leak_the_uuid_in_plain_text(): void
    {
        $token = UrlToken::encode('product', 'super-secret-uuid', 1);

        $this->assertStringNotContainsString('super-secret-uuid', $token);
        $this->assertStringNotContainsString('product', $token);
    }

    #[Test]
    public function a_tampered_payload_is_rejected(): void
    {
        $token = UrlToken::encode('product', 'uuid-123', 1);

        // Flip a character in the payload portion, keeping the signature.
        [$body, $signature] = explode('.', $token, 2);
        $tampered = strrev($body).'.'.$signature;

        $this->expectException(InvalidUrlTokenException::class);
        UrlToken::decode($tampered, 'product');
    }

    #[Test]
    public function a_token_for_another_model_type_is_rejected(): void
    {
        // A token minted for a "vendor" must not open a "product" route.
        $token = UrlToken::encode('vendor', 'uuid-123', 1);

        $this->expectException(InvalidUrlTokenException::class);
        UrlToken::decode($token, 'product');
    }

    #[Test]
    public function an_expired_token_is_rejected(): void
    {
        $token = UrlToken::encode('product', 'uuid-123', 1, ttlDays: 1, issuedAt: time() - (3 * 86400));

        $this->expectException(InvalidUrlTokenException::class);
        UrlToken::decode($token, 'product');
    }

    #[Test]
    public function a_non_expiring_token_stays_valid(): void
    {
        $token = UrlToken::encode('product', 'uuid-123', 1, ttlDays: 0, issuedAt: time() - (365 * 86400));

        $payload = UrlToken::decode($token, 'product');

        $this->assertSame(0, $payload['expires_at']);
    }

    #[Test]
    public function malformed_input_is_rejected(): void
    {
        $this->expectException(InvalidUrlTokenException::class);
        UrlToken::decode('not-a-token', 'product');
    }

    #[Test]
    public function a_token_signed_with_a_different_app_key_is_rejected(): void
    {
        $token = UrlToken::encode('product', 'uuid-123', 1);

        // Rotating APP_KEY must invalidate every outstanding link.
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        UrlToken::flushKeys();

        $this->expectException(InvalidUrlTokenException::class);
        UrlToken::decode($token, 'product');
    }

    #[Test]
    public function isValid_reports_rather_than_throws(): void
    {
        $token = UrlToken::encode('product', 'uuid-123', 1);

        $this->assertTrue(UrlToken::isValid($token, 'product'));
        $this->assertFalse(UrlToken::isValid($token, 'vendor'));
        $this->assertFalse(UrlToken::isValid('garbage', 'product'));
    }

    #[Test]
    public function tokens_are_unique_per_issue_even_for_the_same_record(): void
    {
        $a = UrlToken::encode('product', 'uuid-123', 1, issuedAt: 1000);
        $b = UrlToken::encode('product', 'uuid-123', 1, issuedAt: 2000);

        // Different issue times must produce different tokens, so a token is
        // not a permanent fingerprint of the record.
        $this->assertNotSame($a, $b);
    }

    #[Test]
    public function the_same_inputs_produce_a_stable_token(): void
    {
        $a = UrlToken::encode('product', 'uuid-123', 1, issuedAt: 1000);
        $b = UrlToken::encode('product', 'uuid-123', 1, issuedAt: 1000);

        $this->assertSame($a, $b);
    }
}
