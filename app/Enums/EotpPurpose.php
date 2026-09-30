<?php

namespace App\Enums;

enum EotpPurpose: string
{
    case EmailVerification = 'email_verification';
    case LoginChallenge = 'login_challenge';
    case PasswordReset = 'password_reset';
    case SensitiveAction = 'sensitive_action';

    public function label(): string
    {
        return match ($this) {
            self::EmailVerification => 'Verify your email address',
            self::LoginChallenge => 'Confirm your sign-in',
            self::PasswordReset => 'Reset your password',
            self::SensitiveAction => 'Confirm this action',
        };
    }

    /** Minutes the code stays valid. */
    public function ttlMinutes(): int
    {
        return match ($this) {
            self::EmailVerification => 30,
            self::LoginChallenge => 10,
            self::PasswordReset => 15,
            self::SensitiveAction => 10,
        };
    }

    /** Seconds before a new code may be requested. */
    public function resendCooldownSeconds(): int
    {
        return 60;
    }

    /** How many wrong guesses before the code is destroyed. */
    public function maxAttempts(): int
    {
        return 5;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
