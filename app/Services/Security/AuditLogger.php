<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Append-only record of security- and money-relevant actions.
 *
 * Every entry is written from the server side only. Properties are filtered
 * against a deny-list so a careless caller cannot persist a password, token or
 * one-time code into the log.
 */
class AuditLogger
{
    /** Keys that must never reach the log, whatever the caller passes. */
    private const REDACTED_KEYS = [
        'password', 'password_confirmation', 'current_password',
        'token', 'remember_token', 'two_factor_secret', 'secret',
        'code', 'otp', 'recovery_codes', 'two_factor_recovery_codes',
        'api_key', 'secret_key', 'card', 'cvv', 'signature',
    ];

    public function log(
        string $event,
        ?Model $subject = null,
        ?string $description = null,
        array $properties = [],
        ?User $actor = null,
    ): AuditLog {
        $actor ??= Auth::user();

        return AuditLog::create([
            'user_id' => $actor?->id,
            'event' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $this->redact($properties) ?: null,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 500),
        ]);
    }

    /* ------------------------------------------------------------------ *
     * Convenience wrappers for the events the application actually raises
     * ------------------------------------------------------------------ */

    public function registered(User $user): AuditLog
    {
        return $this->log('auth.registered', $user, 'Account created', ['email' => $user->email], $user);
    }

    public function loggedIn(User $user, bool $twoFactor = false): AuditLog
    {
        return $this->log(
            $twoFactor ? 'auth.login.2fa' : 'auth.login',
            $user,
            $twoFactor ? 'Signed in with two-factor authentication' : 'Signed in',
            [],
            $user,
        );
    }

    public function twoFactorEnabled(User $user, string $method): AuditLog
    {
        return $this->log('security.2fa.enabled', $user, "Two-factor enabled ({$method})", ['method' => $method], $user);
    }

    public function twoFactorDisabled(User $user): AuditLog
    {
        return $this->log('security.2fa.disabled', $user, 'Two-factor disabled', [], $user);
    }

    public function emailVerified(User $user): AuditLog
    {
        return $this->log('auth.email.verified', $user, 'Email address verified', [], $user);
    }

    public function moderation(string $event, Model $subject, string $description, array $properties = []): AuditLog
    {
        return $this->log($event, $subject, $description, $properties);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Recursively strip anything that could carry a credential.
     */
    private function redact(array $properties): array
    {
        $clean = [];

        foreach ($properties as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::REDACTED_KEYS, true)) {
                $clean[$key] = '[redacted]';

                continue;
            }

            $clean[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $clean;
    }
}
