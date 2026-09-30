<?php

namespace App\Models;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Concerns\HasUuid;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'order_id', 'provider', 'status', 'reference', 'provider_reference',
        'currency', 'amount_minor', 'channel', 'gateway_payload',
        'failure_reason', 'refunded_minor', 'initialized_at', 'paid_at', 'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'status' => PaymentStatus::class,
            'amount_minor' => 'integer',
            'refunded_minor' => 'integer',
            'gateway_payload' => 'array',
            'initialized_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Our own unique reference, sent to the gateway and used to reconcile
     * callbacks and webhooks back to this row.
     */
    public static function generateReference(): string
    {
        return 'HB-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(4)));
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Succeeded);
    }

    public function scopeForProvider(Builder $query, PaymentProvider $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    public function formattedAmount(): string
    {
        return Money::format($this->amount_minor, $this->currency);
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::Succeeded;
    }

    public function isRefundable(): bool
    {
        return $this->isSuccessful() && $this->refunded_minor < $this->amount_minor;
    }

    public function refundableMinor(): int
    {
        return max(0, $this->amount_minor - $this->refunded_minor);
    }

    public function providerLabel(): string
    {
        return $this->provider instanceof PaymentProvider
            ? $this->provider->label()
            : ucfirst((string) $this->provider);
    }

    /** Record a verified failure without losing the original payload. */
    public function markFailed(string $reason, array $payload = []): void
    {
        $this->forceFill([
            'status' => PaymentStatus::Failed,
            'failure_reason' => $reason,
            'failed_at' => now(),
            'gateway_payload' => $payload ?: $this->gateway_payload,
        ])->save();
    }
}
