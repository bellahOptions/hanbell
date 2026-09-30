<?php

namespace App\Enums;

enum TwoFactorMethod: string
{
    case None = 'none';
    /** Time-based one-time password — authenticator apps (RFC 6238). */
    case Totp = 'totp';
    /** Email one-time password — a code mailed at login time. */
    case Eotp = 'eotp';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Disabled',
            self::Totp => 'Authenticator app (TOTP)',
            self::Eotp => 'Email code (EOTP)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::None => 'Only your password protects this account.',
            self::Totp => 'Use Google Authenticator, Authy or 1Password for a rotating 6-digit code.',
            self::Eotp => 'We email you a 6-digit code each time you sign in on a new device.',
        };
    }

    public function isEnabled(): bool
    {
        return $this !== self::None;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
