<?php

namespace App\Models;

use App\Enums\EotpPurpose;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An emailed one-time code. Only the hash is stored — the plaintext code exists
 * solely in the email that carries it.
 */
class EmailOtp extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id', 'email', 'purpose', 'code_hash', 'attempts',
        'expires_at', 'consumed_at', 'last_sent_at', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => EotpPurpose::class,
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'last_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->whereNull('consumed_at')->where('expires_at', '>', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isUsable(): bool
    {
        return ! $this->isExpired() && ! $this->isConsumed();
    }

    public function purposeEnum(): EotpPurpose
    {
        return $this->purpose instanceof EotpPurpose
            ? $this->purpose
            : EotpPurpose::from((string) $this->purpose);
    }

    public function registerFailedAttempt(): void
    {
        $this->increment('attempts');

        if ($this->attempts >= $this->purposeEnum()->maxAttempts()) {
            $this->consume();
        }
    }

    public function consume(): void
    {
        if ($this->consumed_at === null) {
            $this->forceFill(['consumed_at' => now()])->save();
        }
    }
}
