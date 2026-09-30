<?php

namespace App\Models;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Concerns\HasUrlToken;
use App\Models\Concerns\HasUuid;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use HasUrlToken;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'number', 'user_id', 'coupon_id', 'email', 'phone', 'customer_name',
        'shipping_recipient_name', 'shipping_phone', 'shipping_line1', 'shipping_line2',
        'shipping_city', 'shipping_state', 'shipping_postal_code', 'shipping_country',
        'notes', 'currency', 'subtotal_minor', 'discount_minor', 'shipping_minor',
        'tax_minor', 'total_minor', 'commission_minor',
        'status', 'payment_status', 'channel',
        'paid_at', 'shipped_at', 'delivered_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'channel' => OrderChannel::class,
            'subtotal_minor' => 'integer',
            'discount_minor' => 'integer',
            'shipping_minor' => 'integer',
            'tax_minor' => 'integer',
            'total_minor' => 'integer',
            'commission_minor' => 'integer',
            'token_version' => 'integer',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /* ------------------------------------------------------------------ *
     * Relationships
     * ------------------------------------------------------------------ */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function vendorOrders(): HasMany
    {
        return $this->hasMany(VendorOrder::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /* ------------------------------------------------------------------ *
     * Scopes
     * ------------------------------------------------------------------ */

    public function scopePaid(Builder $query): Builder
    {
        return $query->whereNotNull('paid_at');
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereNull('paid_at');
    }

    public function scopeWithStatus(Builder $query, OrderStatus|string $status): Builder
    {
        return $query->where('status', $status instanceof OrderStatus ? $status->value : $status);
    }

    public function scopeBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /* ------------------------------------------------------------------ *
     * Presentation
     * ------------------------------------------------------------------ */

    public function formattedTotal(): string
    {
        return Money::format($this->total_minor, $this->currency);
    }

    public function formattedSubtotal(): string
    {
        return Money::format($this->subtotal_minor, $this->currency);
    }

    public function itemCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function shippingAddressLines(): array
    {
        return array_values(array_filter([
            $this->shipping_recipient_name,
            $this->shipping_line1,
            $this->shipping_line2,
            $this->shipping_city,
            $this->shipping_state,
            $this->shipping_postal_code,
            $this->shipping_country === 'NG' ? 'Nigeria' : $this->shipping_country,
        ]));
    }

    public function publicUrl(): string
    {
        return route('storefront.orders.show', ['order' => $this->urlToken()]);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [OrderStatus::Pending, OrderStatus::Paid], true);
    }

    /* ------------------------------------------------------------------ *
     * State
     * ------------------------------------------------------------------ */

    /**
     * Move the order to a new status, refusing illegal jumps.
     * Returns false when the transition is not permitted.
     */
    public function transitionTo(OrderStatus $next): bool
    {
        $current = $this->status instanceof OrderStatus ? $this->status : OrderStatus::from($this->status);

        if ($current === $next) {
            return true;
        }

        if (! $current->canTransitionTo($next)) {
            return false;
        }

        $attributes = ['status' => $next];

        if ($next === OrderStatus::Shipped && $this->shipped_at === null) {
            $attributes['shipped_at'] = now();
        }

        if ($next === OrderStatus::Delivered && $this->delivered_at === null) {
            $attributes['delivered_at'] = now();
        }

        if ($next === OrderStatus::Cancelled && $this->cancelled_at === null) {
            $attributes['cancelled_at'] = now();
        }

        $this->forceFill($attributes)->save();

        return true;
    }

    /** Human-readable order number, e.g. HB-2025-000123. */
    public static function generateNumber(): string
    {
        $year = now()->format('Y');
        $sequence = (int) static::withTrashed()->whereYear('created_at', $year)->count() + 1;

        return sprintf('HB-%s-%06d', $year, $sequence);
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