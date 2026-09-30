<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Wishlist extends Model
{
    use HasUuid;

    protected $fillable = ['user_id', 'guest_token'];

    protected static function booted(): void
    {
        static::creating(function (self $wishlist): void {
            $wishlist->guest_token ??= $wishlist->user_id === null ? Str::random(48) : null;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function itemCount(): int
    {
        return $this->items()->count();
    }
}
