<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Coupon extends Model
{
    use HasUuid;

    protected $fillable = [
        'code', 'name', 'description', 'type', 'value', 'minimum_order_minor',
        'maximum_discount_minor', 'usage_limit', 'usage_limit_per_user',
        'vendor_id', 'is_active', 'starts_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'minimum_order_minor' => 'integer',
            'maximum_discount_minor' => 'integer',
            'usage_limit' => 'integer',
            'usage_limit_per_user' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $coupon): void {
            $coupon->code = Str::upper($coupon->code);
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isPercentage(): bool
    {
        return $this->type === 'percentage';
    }

    /** Whether the coupon may be used right now, ignoring the basket. */
    public function isRedeemable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return $this->usage_limit === null || $this->used_count < $this->usage_limit;
    }

    /**
     * Discount this coupon yields on a given subtotal, capped appropriately.
     * Returns 0 when the coupon does not apply.
     */
    public function discountFor(int $subtotalMinor): int
    {
        if (! $this->isRedeemable()) {
            return 0;
        }

        if ($this->minimum_order_minor !== null && $subtotalMinor < $this->minimum_order_minor) {
            return 0;
        }

        $discount = $this->isPercentage()
            ? (int) round($subtotalMinor * ($this->value / 100))
            : (int) $this->value;

        if ($this->maximum_discount_minor !== null) {
            $discount = min($discount, (int) $this->maximum_discount_minor);
        }

        // Never discount below zero.
        return max(0, min($discount, $subtotalMinor));
    }

    public function label(): string
    {
        return $this->isPercentage()
            ? $this->value.'% off'
            : \App\Support\Money::format((int) $this->value).' off';
    }
}
