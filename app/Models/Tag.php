<?php

namespace App\Models;

use App\Models\Concerns\HasUrlToken;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Tag extends Model
{
    use HasFactory;
    use HasSlug;
    use HasUrlToken;
    use HasUuid;

    protected $fillable = ['name', 'slug', 'type', 'use_count'];

    protected function casts(): array
    {
        return ['use_count' => 'integer'];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function publicUrl(): string
    {
        return route('storefront.tags.show', ['tag' => $this->urlToken(), 'slug' => $this->slug]);
    }

    /** Recompute the denormalised use_count from the pivot. */
    public function refreshUseCount(): void
    {
        $this->forceFill([
            'use_count' => $this->products()
                ->where('products.status', 'published')
                ->count(),
        ])->save();
    }

    public function displayName(): string
    {
        return Str::headline($this->name);
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