<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    use HasUuid;

    /** Attempts are written once; there is nothing to update. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'email', 'ip_address', 'user_agent', 'successful', 'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public static function record(
        ?string $email,
        bool $successful,
        ?string $reason = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): self {
        return static::create([
            'email' => $email ? strtolower(trim($email)) : null,
            'ip_address' => $ip,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
            'successful' => $successful,
            'failure_reason' => $reason,
        ]);
    }

    /** Recent failures for an email or IP, used for lockout decisions. */
    public static function recentFailures(?string $email, ?string $ip, int $minutes = 15): int
    {
        return static::query()
            ->where('successful', false)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->where(function ($query) use ($email, $ip): void {
                if ($email) {
                    $query->where('email', strtolower(trim($email)));
                }
                if ($ip) {
                    $query->orWhere('ip_address', $ip);
                }
            })
            ->count();
    }
}
