<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\Concerns\HasUrlToken;
use App\Models\Concerns\HasUuid;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A per-vendor slice of one order. Commission, payout and fulfilment status
 * live here because each vendor ships and is paid independently.
 */
class VendorOrder extends Model
{
    use HasUrlToken;
    use HasUuid;

    protected $fillable = [
        'order_id', 'vendor_id', 'number', 'subtotal_minor', 'shipping_minor',
        'commission_minor', 'payout_minor', 'commission_percent', 'status',
        'shipped_at', 'delivered_at', 'tracking_number', 'courier',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal_minor' => 'integer',
            'shipping_minor' => 'integer',
            'commission_minor' => 'integer',
            'payout_minor' => 'integer',
            'commission_percent' => 'decimal:2',
            'token_version' => 'integer',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function formattedSubtotal(): string
    {
        return Money::format($this->subtotal_minor, $this->order?->currency ?? 'NGN');
    }

    public function formattedPayout(): string
    {
        return Money::format($this->payout_minor, $this->order?->currency ?? 'NGN');
    }

    public function markShipped(?string $courier = null, ?string $tracking = null): void
    {
        $this->forceFill([
            'status' => OrderStatus::Shipped,
            'shipped_at' => $this->shipped_at ?? now(),
            'courier' => $courier ?? $this->courier,
            'tracking_number' => $tracking ?? $this->tracking_number,
        ])->save();
    }

    public function markDelivered(): void
    {
        $this->forceFill([
            'status' => OrderStatus::Delivered,
            'delivered_at' => $this->delivered_at ?? now(),
        ])->save();
    }

    /**
     * Route binding for this model.
     *
     * Declared here rather than left to the trait because Spatie's HasSlug also
     * defines resolveRouteBinding, and two traits providing the same method is a
     * fatal collision. Delegating to resolveTokenBinding() keeps the token
     * verification in one place. See App\Models\Concerns\HasUrlToken.
     */
    public function resolveRouteBinding($value, $field = null): ?\Illuminate\Database\Eloquent\Model
    {
        return $this->resolveTokenBinding($value);
    }
}