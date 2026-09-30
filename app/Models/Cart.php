<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A cart belongs either to a signed-in user or to a guest holding an opaque
 * cookie token. On login the guest cart is merged into the user's cart.
 *
 * No price is stored on a cart line: totals are always recalculated from the
 * live product so a stale or tampered client price can never be honoured.
 */
class Cart extends Model
{
    use HasUuid;

    protected $fillable = ['user_id', 'guest_token', 'currency', 'last_activity_at'];

    protected function casts(): array
    {
        return ['last_activity_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $cart): void {
            $cart->guest_token ??= $cart->user_id === null ? self::newGuestToken() : null;
            $cart->currency ??= config('hanbell.currency.default', 'NGN');
        });
    }

    public static function newGuestToken(): string
    {
        return Str::random(48);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    public function itemCount(): int
    {
        return (int) $this->items()->sum('quantity');
    }

    public function isEmpty(): bool
    {
        return ! $this->items()->exists();
    }

    public function touchActivity(): void
    {
        $this->forceFill(['last_activity_at' => now()])->saveQuietly();
    }
}
