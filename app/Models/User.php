<?php

namespace App\Models;

use App\Enums\TwoFactorMethod;
use App\Models\Concerns\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasRoles;
    use HasUuid;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar_path',
        'locale',
        'currency',
        'two_factor_method',
        'email_verified_at',
        'is_vendor',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_method' => TwoFactorMethod::class,
            // Encrypted at rest: a database dump never exposes live TOTP seeds
            // or recovery codes.
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'is_vendor' => 'boolean',
            'last_login_at' => 'datetime',
            'token_version' => 'integer',
        ];
    }

    /* ------------------------------------------------------------------ *
     * Relationships
     * ------------------------------------------------------------------ */

    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class, 'owner_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function wishlist(): HasOne
    {
        return $this->hasOne(Wishlist::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    /* ------------------------------------------------------------------ *
     * Role helpers
     * ------------------------------------------------------------------ */

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isVendor(): bool
    {
        return $this->hasRole('vendor') || $this->is_vendor;
    }

    /** Administrators do not shop â€” they have no cart, wishlist or checkout. */
    public function canShop(): bool
    {
        return ! $this->isAdmin();
    }

    /**
     * Where this user belongs after authenticating. Admins land in the admin
     * panel rather than the storefront.
     */
    public function postLoginRoute(): string
    {
        return $this->isAdmin()
            ? route('admin.dashboard')
            : route('storefront.home');
    }

    /* ------------------------------------------------------------------ *
     * Two-factor helpers
     * ------------------------------------------------------------------ */

    public function twoFactorMethod(): TwoFactorMethod
    {
        return $this->two_factor_method instanceof TwoFactorMethod
            ? $this->two_factor_method
            : TwoFactorMethod::tryFrom((string) ($this->two_factor_method ?? 'none')) ?? TwoFactorMethod::None;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->twoFactorMethod()->isEnabled() && $this->two_factor_confirmed_at !== null;
    }

    /** Used by ad targeting to distinguish new from returning customers. */
    public function hasPaidOrder(): bool
    {
        return $this->orders()->whereNotNull('paid_at')->exists();
    }

    /* ------------------------------------------------------------------ *
     * Display helpers
     * ------------------------------------------------------------------ */

    public function initials(): string
    {
        return Str::of($this->name)
            ->squish()
            ->explode(' ')
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }

    public function firstName(): string
    {
        return Str::before($this->name, ' ') ?: (string) $this->name;
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            $user->locale ??= config('app.locale', 'en');
            $user->currency ??= config('hanbell.currency.default', 'NGN');
        });
    }
}
