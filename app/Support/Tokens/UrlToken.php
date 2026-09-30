<?php

namespace App\Support\Tokens;

/**
 * Opaque, signed public URL tokens.
 *
 * Public URLs never carry a sequential id, and they do not carry a raw uuid
 * either. Instead a URL segment is an HMAC-signed envelope that binds:
 *
 *   - the token format version (lets the scheme evolve without breaking links)
 *   - the model type ("product"), so a variant token cannot be replayed
 *     against a product route (type confusion)
 *   - the record's uuid
 *   - the record's `token_version`, so bumping that column revokes every
 *     previously issued link for one record without changing its uuid
 *   - an issue timestamp, giving tokens a bounded lifetime
 *
 * The signature is an HMAC-SHA256 over the payload using a key derived from
 * APP_KEY, so a token cannot be forged by editing a decoded payload. The value
 * is base64url-encoded, which makes it URL-safe and opaque to a casual reader.
 *
 * Tampering therefore fails in three independent ways (bad signature, expired
 * timestamp, unknown uuid) and revocation is a single column update.
 */
final class UrlToken
{
    /** Bump only if the payload shape changes in a breaking way. */
    public const FORMAT_VERSION = 1;

    private const SEPARATOR = '.';

    /** Cached derived signing keys, keyed by purpose. */
    private static array $keys = [];

    /**
     * Build a token for a model type + uuid.
     */
    public static function encode(
        string $type,
        string $uuid,
        int $version = 1,
        ?int $ttlDays = null,
        ?int $issuedAt = null,
    ): string {
        $ttlDays ??= (int) config('hanbell.tokens.ttl_days', 30);
        $issuedAt ??= time();
        $expiresAt = $ttlDays > 0 ? $issuedAt + ($ttlDays * 86400) : 0;

        $payload = [
            'v' => self::FORMAT_VERSION,
            't' => $type,
            'u' => $uuid,
            'r' => $version,
            'i' => $issuedAt,
            'e' => $expiresAt,
        ];

        $body = self::base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = self::sign($body, $type);

        return $body.self::SEPARATOR.$signature;
    }

    /**
     * Verify and decode a token.
     *
     * @param  string  $expectedType  the model type this route expects
     * @return array{type:string,uuid:string,version:int,issued_at:int,expires_at:int}
     *
     * @throws InvalidUrlTokenException
     */
    public static function decode(string $token, ?string $expectedType = null): array
    {
        $token = trim($token);

        // Optional trailing segment is allowed (e.g. a slug) — decode only the
        // token portion so "/product/<token>/<slug>" still resolves.
        $parts = explode(self::SEPARATOR, $token);

        if (count($parts) < 2) {
            throw InvalidUrlTokenException::malformed();
        }

        $signature = array_pop($parts);
        $body = implode(self::SEPARATOR, $parts);

        $payload = json_decode(self::base64UrlDecode($body) ?? '', true);

        if (! is_array($payload)) {
            throw InvalidUrlTokenException::malformed();
        }

        $type = (string) ($payload['t'] ?? '');
        $uuid = (string) ($payload['u'] ?? '');
        $version = (int) ($payload['r'] ?? 0);
        $issuedAt = (int) ($payload['i'] ?? 0);
        $expiresAt = (int) ($payload['e'] ?? 0);
        $format = (int) ($payload['v'] ?? 0);

        if ($type === '' || $uuid === '' || $format !== self::FORMAT_VERSION) {
            throw InvalidUrlTokenException::malformed();
        }

        // Constant-time comparison; the signature must match for the type the
        // *route* expects, which is what prevents cross-model token reuse.
        if (! hash_equals(self::sign($body, $expectedType ?? $type), $signature)) {
            // Also accept a signature computed for the payload's own type, so a
            // caller that does not know the type up front can still decode.
            if ($expectedType !== null || ! hash_equals(self::sign($body, $type), $signature)) {
                throw InvalidUrlTokenException::badSignature();
            }
        }

        if ($expectedType !== null && $type !== $expectedType) {
            throw InvalidUrlTokenException::wrongType($expectedType, $type);
        }

        if ($expiresAt > 0 && $expiresAt < time()) {
            throw InvalidUrlTokenException::expired();
        }

        return [
            'type' => $type,
            'uuid' => $uuid,
            'version' => $version,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Inspect a token without enforcing expiry. Used by the rotation command
     * and by tests; never by route binding.
     *
     * @return array<string,mixed>|null
     */
    public static function peek(string $token): ?array
    {
        $parts = explode(self::SEPARATOR, $token);
        if (count($parts) < 2) {
            return null;
        }
        array_pop($parts);
        $payload = json_decode(self::base64UrlDecode(implode(self::SEPARATOR, $parts)) ?? '', true);

        return is_array($payload) ? $payload : null;
    }

    /** True when the token verifies and has not expired. */
    public static function isValid(string $token, ?string $expectedType = null): bool
    {
        try {
            self::decode($token, $expectedType);

            return true;
        } catch (InvalidUrlTokenException) {
            return false;
        }
    }

    /**
     * A short, stable, non-reversible handle for a record. Used where a token
     * would be overkill (cache keys, log correlation).
     */
    public static function fingerprint(string $type, string $uuid): string
    {
        return substr(hash_hmac('sha256', $type.':'.$uuid, self::key($type)), 0, 16);
    }

    /* ------------------------------------------------------------------ *
     * Internals
     * ------------------------------------------------------------------ */

    private static function sign(string $body, string $type): string
    {
        return self::base64UrlEncode(hash_hmac('sha256', $body, self::key($type), true));
    }

    /**
     * Derive a per-type signing key from APP_KEY.
     *
     * Deriving per type means a token signed for one model type can never
     * verify for another, on top of the explicit type check.
     */
    private static function key(string $type): string
    {
        return self::$keys[$type] ??= hash_hmac(
            'sha256',
            'hanbell.url-token.'.$type,
            self::appKey(),
            true
        );
    }

    /**
     * The raw application key. Laravel stores it base64-encoded in APP_KEY.
     */
    private static function appKey(): string
    {
        $key = (string) config('app.key');

        if ($key === '') {
            return 'hanbell-insecure-fallback-key';
        }

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);

            return $decoded === false ? $key : $decoded;
        }

        return $key;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): ?string
    {
        $padded = strtr($value, '-_', '+/');
        $padded .= str_repeat('=', (4 - strlen($padded) % 4) % 4);

        $decoded = base64_decode($padded, true);

        return $decoded === false ? null : $decoded;
    }

    /** Forget derived keys — used by tests when APP_KEY is swapped. */
    public static function flushKeys(): void
    {
        self::$keys = [];
    }
}
