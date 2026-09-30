<?php

namespace App\Models;

use App\Enums\Gender;
use App\Models\Concerns\HasUrlToken;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * A top-level storefront department (Women, Men, Kids, Accessories…).
 * Categories hang beneath departments; products reference both so a
 * department listing never needs a join through categories.
 */
class Department extends Model
{
    use HasFactory;
    use HasSlug;
    use HasUrlToken;
    use HasUuid;

    protected $fillable = [
        'name', 'slug', 'gender', 'icon', 'image_path', 'description',
        'position', 'is_active', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'is_active' => 'boolean',
            'position' => 'integer',
            'token_version' => 'integer',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('name');
    }

    public function publicUrl(): string
    {
        return route('storefront.departments.show', [
            'department' => $this->urlToken(),
            'slug' => $this->slug,
        ]);
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