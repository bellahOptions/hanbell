<?php

namespace App\Models;

use App\Enums\VendorStatus;
use App\Models\Concerns\HasUrlToken;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Vendor extends Model
{
    use HasFactory;
    use HasSlug;
    use HasUrlToken;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'legal_name',
        'email',
        'phone',
        'whatsapp',
        'website',
        'description',
        'story',
        'logo_path',
        'banner_path',
        'address_line',
        'city',
        'state',
        'country',
        'status',
        'status_reason',
        'commission_percent',
        'is_featured',
        'applied_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => VendorStatus::class,
            'commission_percent' => 'decimal:2',
            'is_featured' => 'boolean',
            'applied_at' => 'datetime',
            'approved_at' => 'datetime',
            'token_version' => 'integer',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    /* ------------------------------------------------------------------ *
     * Relationships
     * ------------------------------------------------------------------ */

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    /* ------------------------------------------------------------------ *
     * Scopes
     * ------------------------------------------------------------------ */

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', VendorStatus::Approved);
    }

    public function scopeIsFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('city', 'like', "%{$term}%")
                ->orWhere('state', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /* ------------------------------------------------------------------ *
     * Behaviour
     * ------------------------------------------------------------------ */

    public function isApproved(): bool
    {
        return $this->status === VendorStatus::Approved;
    }

    public function approve(): void
    {
        $this->forceFill([
            'status' => VendorStatus::Approved,
            'status_reason' => null,
            'approved_at' => now(),
        ])->save();

        $this->owner?->forceFill(['is_vendor' => true])->save();
        $this->owner?->assignRole('vendor');
    }

    public function reject(string $reason): void
    {
        $this->forceFill([
            'status' => VendorStatus::Rejected,
            'status_reason' => $reason,
        ])->save();
    }

    public function suspend(string $reason): void
    {
        $this->forceFill([
            'status' => VendorStatus::Suspended,
            'status_reason' => $reason,
        ])->save();
    }

    /**
     * Commission rate that applies to this vendor, before category/default
     * fallbacks are considered.
     */
    public function commissionPercent(): ?float
    {
        return $this->commission_percent === null ? null : (float) $this->commission_percent;
    }

    public function publicUrl(): string
    {
        return route('storefront.vendors.show', ['vendor' => $this->urlToken(), 'slug' => $this->slug]);
    }

    public function location(): string
    {
        return collect([$this->city, $this->state])->filter()->implode(', ') ?: 'Nigeria';
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