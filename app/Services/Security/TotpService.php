<?php

namespace App\Services\Security;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

/**
 * Time-based one-time passwords (RFC 6238) for authenticator apps.
 *
 * The QR code is rendered locally with bacon/bacon-qr-code rather than pointing
 * at a third-party image service: the provisioning URI contains the shared
 * secret, so sending it to someone else's server would leak the second factor.
 */
class TotpService
{
    public const RECOVERY_CODE_COUNT = 8;

    public function __construct(private readonly Google2FA $engine = new Google2FA) {}

    /**
     * A fresh base32 secret.
     */
    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey();
    }

    /**
     * The otpauth:// URI an authenticator app scans.
     */
    public function provisioningUri(User $user, string $secret): string
    {
        return $this->engine->getQRCodeUrl(
            config('hanbell.name', 'HanbellShop'),
            $user->email,
            $secret,
        );
    }

    /**
     * Render a provisioning URI as an inline SVG QR code.
     * SVG (not PNG) keeps this dependency-free — no GD or Imagick required.
     */
    public function qrCodeSvg(string $provisioningUri, int $size = 220): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 1),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($provisioningUri);
    }

    /**
     * Verify a submitted 6-digit code, allowing one time-step of clock drift in
     * either direction.
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if ($code === '' || $secret === '') {
            return false;
        }

        try {
            return (bool) $this->engine->verifyKey($secret, $code, $window);
        } catch (\Throwable) {
            // A malformed secret or code is simply not valid.
            return false;
        }
    }

    /**
     * The current code for a secret. Used only in tests and by the enrolment
     * screen for confirmation.
     */
    public function currentCode(string $secret): string
    {
        return $this->engine->getCurrentOtp($secret);
    }

    /**
     * Generate single-use recovery codes.
     *
     * @return list<string>
     */
    public function generateRecoveryCodes(int $count = self::RECOVERY_CODE_COUNT): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            // Grouped for legibility when read off paper.
            $codes[] = strtoupper(bin2hex(random_bytes(2)).'-'.bin2hex(random_bytes(2)));
        }

        return $codes;
    }

    /**
     * Consume a recovery code. Returns the remaining codes when it matched, or
     * null when the code was not valid (so the caller never learns which codes
     * exist without one).
     *
     * @param  list<string>  $existing
     * @return list<string>|null
     */
    public function consumeRecoveryCode(array $existing, string $submitted): ?array
    {
        $submitted = strtoupper(trim($submitted));

        $normalised = array_map(fn ($code) => strtoupper(trim((string) $code)), $existing);

        $index = array_search($submitted, $normalised, true);

        if ($index === false) {
            return null;
        }

        unset($normalised[$index]);

        return array_values($normalised);
    }
}
