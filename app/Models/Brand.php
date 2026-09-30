<?php

namespace App\Models;

use App\Models\Concerns\HasUrlToken;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Brand extends Model
{
    use HasFactory;
    use HasSlug;
    use HasUrlToken;
    use HasUuid;

    protected $fillable = [
        'vendor_id', 'name', 'slug', 'description', 'logo_path', 'country',
        'is_active', 'is_featured', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'token_version' => 'integer',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function publicUrl(): string
    {
        return route('storefront.brands.show', ['brand' => $this->urlToken(), 'slug' => $this->slug]);
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