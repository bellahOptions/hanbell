<?php

namespace App\Services\Security;

use App\Enums\EotpPurpose;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

/**
 * Email one-time passwords.
 *
 * Three properties matter:
 *   - the plaintext code is never stored and never logged, only its hash;
 *   - issuing a new code invalidates every previous outstanding code for that
 *     email + purpose, so an old email cannot still be used;
 *   - attempts are capped and rate-limited, so a 6-digit code cannot be
 *     brute-forced.
 */
class EotpService
{
    /** Number of digits in a generated code. */
    public const CODE_LENGTH = 6;

    /**
     * Issue a fresh code, invalidating any outstanding ones.
     *
     * @return array{code:string, expires_at:\Illuminate\Support\Carbon, otp:EmailOtp}
     */
    public function issue(
        string $email,
        EotpPurpose $purpose,
        ?User $user = null,
        ?string $ip = null,
    ): array {
        $email = strtolower(trim($email));

        $this->guardResendCooldown($email, $purpose);

        // Burn any outstanding codes for this email + purpose.
        EmailOtp::where('email', $email)
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = $this->generateCode();

        $otp = EmailOtp::create([
            'user_id' => $user?->id,
            'email' => $email,
            'purpose' => $purpose->value,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($purpose->ttlMinutes()),
            'last_sent_at' => now(),
            'ip_address' => $ip,
        ]);

        return [
            'code' => $code,
            'expires_at' => $otp->expires_at,
            'otp' => $otp,
        ];
    }

    /**
     * Verify a submitted code.
     *
     * Returns true only for a live, unconsumed, unexpired code with attempts
     * remaining. A wrong guess increments the attempt counter, and exhausting
     * it destroys the code outright.
     */
    public function verify(string $email, EotpPurpose $purpose, string $code): bool
    {
        $email = strtolower(trim($email));
        $code = preg_replace('/\D/', '', $code) ?? '';

        if ($code === '') {
            return false;
        }

        $otp = EmailOtp::where('email', $email)
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if (! $otp) {
            return false;
        }

        if ($otp->isExpired()) {
            $otp->consume();

            return false;
        }

        if ($otp->attempts >= $purpose->maxAttempts()) {
            $otp->consume();

            return false;
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->registerFailedAttempt();

            return false;
        }

        $otp->consume();

        return true;
    }

    /**
     * Whether this email has a usable code right now — used by the UI to decide
     * which step to show after a page refresh.
     */
    public function hasActiveCode(string $email, EotpPurpose $purpose): bool
    {
        return EmailOtp::where('email', strtolower(trim($email)))
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->exists();
    }

    /** Seconds until a new code may be requested; 0 when it may be now. */
    public function secondsUntilResend(string $email, EotpPurpose $purpose): int
    {
        $otp = EmailOtp::where('email', strtolower(trim($email)))
            ->where('purpose', $purpose->value)
            ->latest()
            ->first();

        if (! $otp?->last_sent_at) {
            return 0;
        }

        $readyAt = $otp->last_sent_at->copy()->addSeconds($purpose->resendCooldownSeconds());

        return max(0, (int) now()->diffInSeconds($readyAt, false));
    }

    public function consumeAll(string $email, EotpPurpose $purpose): void
    {
        EmailOtp::where('email', strtolower(trim($email)))
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);
    }

    /* ------------------------------------------------------------------ */

    /**
     * A cryptographically random numeric code, zero-padded so every code has
     * the same length (a leading zero must not shorten it).
     */
    private function generateCode(): string
    {
        $max = (10 ** self::CODE_LENGTH) - 1;

        return str_pad((string) random_int(0, $max), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private function guardResendCooldown(string $email, EotpPurpose $purpose): void
    {
        $wait = $this->secondsUntilResend($email, $purpose);

        if ($wait > 0) {
            throw new RuntimeException(
                "Please wait {$wait} more second(s) before requesting another code."
            );
        }

        // A second, IP-independent ceiling so an attacker cannot generate codes
        // for many addresses in parallel.
        $key = 'eotp:issue:'.$email.':'.$purpose->value;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw new RuntimeException('Too many codes requested. Please try again later.');
        }

        RateLimiter::hit($key, 3600);
    }
}
