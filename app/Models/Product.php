<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\ProductStatus;
use App\Models\Concerns\HasUrlToken;
use App\Models\Concerns\HasUuid;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    use HasFactory;
    use HasSlug;
    use HasUrlToken;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'vendor_id', 'category_id', 'department_id', 'brand_id',
        'name', 'slug', 'sku', 'summary', 'description',
        'price_minor', 'compare_at_price_minor', 'cost_minor', 'currency',
        'status', 'status_reason',
        'is_featured', 'is_new_arrival', 'is_trending', 'is_handmade',
        'gender', 'material', 'care_instructions', 'country_of_origin', 'made_in_city',
        'weight_grams',
        'translations',
        'meta_title', 'meta_description', 'meta_keywords', 'og_image_path',
        'canonical_url', 'is_indexable',
        'llm_summary', 'llm_attributes',
        'submitted_at', 'approved_at', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'gender' => Gender::class,
            'price_minor' => 'integer',
            'compare_at_price_minor' => 'integer',
            'cost_minor' => 'integer',
            'is_featured' => 'boolean',
            'is_new_arrival' => 'boolean',
            'is_trending' => 'boolean',
            'is_handmade' => 'boolean',
            'is_indexable' => 'boolean',
            'weight_grams' => 'integer',
            'views_count' => 'integer',
            'sales_count' => 'integer',
            'rating_count' => 'integer',
            'rating_average' => 'decimal:2',
            'translations' => 'array',
            'llm_attributes' => 'array',
            'token_version' => 'integer',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            // A live URL must never change underneath a shared link; SEO
            // corrections go through an explicit admin redirect instead.
            ->doNotGenerateSlugsOnUpdate();
    }

    /* ------------------------------------------------------------------ *
     * Relationships
     * ------------------------------------------------------------------ */

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('position');
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function primaryInventory(): HasOne
    {
        return $this->hasOne(Inventory::class)->whereNull('variant_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'attribute_product')
            ->withPivot('attribute_value_id')
            ->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true)->latest();
    }

    /**
     * Recompute the denormalised rating aggregate from approved reviews.
     * Called whenever a review is approved, rejected or removed.
     */
    public function refreshRating(): void
    {
        $approved = $this->reviews()->where('is_approved', true);

        $count = (clone $approved)->count();
        $average = $count > 0 ? (float) (clone $approved)->avg('rating') : 0.0;

        $this->forceFill([
            'rating_count' => $count,
            'rating_average' => round($average, 2),
        ])->saveQuietly();
    }

    /* ------------------------------------------------------------------ *
     * Scopes
     * ------------------------------------------------------------------ */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Published)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function scopeIndexable(Builder $query): Builder
    {
        return $query->published()->where('is_indexable', true);
    }

    public function scopeIsFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeNewArrivals(Builder $query): Builder
    {
        return $query->where('is_new_arrival', true);
    }

    public function scopeTrending(Builder $query): Builder
    {
        return $query->where('is_trending', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereHas('inventories', function (Builder $q): void {
            $q->whereRaw('quantity_on_hand - quantity_reserved > 0');
        });
    }

    public function scopeForVendor(Builder $query, int|Vendor $vendor): Builder
    {
        return $query->where('vendor_id', $vendor instanceof Vendor ? $vendor->id : $vendor);
    }

    public function scopePriceBetween(Builder $query, ?int $min, ?int $max): Builder
    {
        return $query->when($min !== null, fn (Builder $q) => $q->where('price_minor', '>=', $min))
            ->when($max !== null, fn (Builder $q) => $q->where('price_minor', '<=', $max));
    }

    /**
     * Full-text-ish search across the fields a shopper would type.
     * SQLite and MySQL both handle LIKE; this stays portable by design.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->where('name', 'like', $like)
                ->orWhere('summary', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhere('material', 'like', $like)
                ->orWhere('made_in_city', 'like', $like)
                ->orWhereHas('vendor', fn (Builder $v) => $v->where('name', 'like', $like))
                ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', $like))
                ->orWhereHas('tags', fn (Builder $t) => $t->where('name', 'like', $like));
        });
    }

    /* ------------------------------------------------------------------ *
     * Pricing
     * ------------------------------------------------------------------ */

    /** Effective price, preferring the variant's own price when set. */
    public function priceFor(?ProductVariant $variant = null): int
    {
        if ($variant?->price_minor !== null) {
            return (int) $variant->price_minor;
        }

        return (int) $this->price_minor;
    }

    public function compareAtPriceFor(?ProductVariant $variant = null): ?int
    {
        $variantPrice = $variant?->compare_at_price_minor;

        if ($variantPrice !== null) {
            return (int) $variantPrice;
        }

        return $this->compare_at_price_minor === null ? null : (int) $this->compare_at_price_minor;
    }

    public function hasDiscount(?ProductVariant $variant = null): bool
    {
        $compare = $this->compareAtPriceFor($variant);

        return $compare !== null && $compare > $this->priceFor($variant);
    }

    /** Discount as a whole percentage, for the "-25%" ribbon. */
    public function discountPercent(?ProductVariant $variant = null): ?int
    {
        if (! $this->hasDiscount($variant)) {
            return null;
        }

        $compare = $this->compareAtPriceFor($variant);
        $price = $this->priceFor($variant);

        if ($compare <= 0) {
            return null;
        }

        return (int) round((($compare - $price) / $compare) * 100);
    }

    public function formattedPrice(?ProductVariant $variant = null): string
    {
        return Money::format($this->priceFor($variant), $this->currency);
    }

    /** Lowest price across active variants — the "from ₦X" figure. */
    public function lowestPrice(): int
    {
        $variantMin = $this->relationLoaded('activeVariants')
            ? $this->activeVariants->min(fn (ProductVariant $v) => $v->price_minor ?? $this->price_minor)
            : $this->activeVariants()->min('price_minor');

        return (int) ($variantMin ?: $this->price_minor);
    }

    /* ------------------------------------------------------------------ *
     * Stock
     * ------------------------------------------------------------------ */

    public function availableQuantity(?ProductVariant $variant = null): int
    {
        if ($variant !== null) {
            $variant->loadMissing('inventory');

            return $variant->inventory?->available() ?? 0;
        }

        $this->loadMissing('inventories');

        // With variants, availability is the sum of their stock; without
        // variants it is the product-level row.
        $relevant = $this->inventories->whereNotNull('variant_id');

        if ($relevant->isEmpty()) {
            $relevant = $this->inventories->whereNull('variant_id');
        }

        return (int) $relevant->sum(fn (Inventory $i) => $i->available());
    }

    public function isInStock(?ProductVariant $variant = null): bool
    {
        if ($this->availableQuantity($variant) > 0) {
            return true;
        }

        if ($variant !== null) {
            return $variant->inventory?->allow_backorder === true;
        }

        return $this->productLevelInventory()?->allow_backorder === true;
    }

    public function isLowStock(?ProductVariant $variant = null): bool
    {
        $available = $this->availableQuantity($variant);

        $threshold = $variant?->inventory?->low_stock_threshold
            ?? $this->productLevelInventory()?->low_stock_threshold
            ?? 3;

        return $available > 0 && $available <= $threshold;
    }

    /**
     * The product-level inventory row (the one with no variant).
     *
     * Reads the already-loaded `inventories` relation when it is present, so
     * rendering a product card does not cost an extra query per product — and
     * so it does not trip `preventLazyLoading`, which correctly treats an
     * un-loaded `primaryInventory` access in a loop as the N+1 it is.
     */
    private function productLevelInventory(): ?Inventory
    {
        if ($this->relationLoaded('inventories')) {
            return $this->inventories->firstWhere('variant_id', null);
        }

        return $this->inventories()->whereNull('variant_id')->first();
    }

    /* ------------------------------------------------------------------ *
     * Media
     * ------------------------------------------------------------------ */

    public function primaryImage(): ?ProductMedia
    {
        $this->loadMissing('media');

        return $this->media->firstWhere('is_primary', true) ?? $this->media->first();
    }

    public function primaryImageUrl(): string
    {
        return $this->primaryImage()?->url() ?? self::placeholderImageUrl();
    }

    /** Stable placeholder so a product card never renders a broken image. */
    public static function placeholderImageUrl(): string
    {
        return asset('images/placeholder-600x300.jpg');
    }

    /* ------------------------------------------------------------------ *
     * Presentation
     * ------------------------------------------------------------------ */

    public function publicUrl(): string
    {
        return route('storefront.products.show', [
            'product' => $this->urlToken(),
            'slug' => $this->slug,
        ]);
    }

    public function metaTitle(): string
    {
        return $this->meta_title
            ?: $this->name.' — '.($this->vendor?->name ?? config('hanbell.name'));
    }

    public function metaDescription(): string
    {
        return $this->meta_description
            ?: Str::limit(strip_tags((string) ($this->summary ?: $this->description)), 155);
    }

    /**
     * The compact factual blurb surfaced to language models and in llms.txt.
     * Generated on the fly when an administrator has not written one.
     */
    public function llmSummary(): string
    {
        if (filled($this->llm_summary)) {
            return (string) $this->llm_summary;
        }

        $parts = array_filter([
            $this->name,
            'by '.($this->vendor?->name ?? 'a Nigerian brand'),
            $this->category?->name ? 'in '.$this->category->name : null,
            'priced at '.$this->formattedPrice(),
            $this->made_in_city ? 'made in '.$this->made_in_city.', Nigeria' : 'made in Nigeria',
        ]);

        return Str::finish(implode(' — ', $parts), '.');
    }

    /**
     * Translation for the active locale, falling back to the base content.
     */
    public function translated(string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $translations = $this->translations ?? [];

        return $translations[$locale][$field]
            ?? $translations['en'][$field]
            ?? $this->getAttribute($field);
    }

    /* ------------------------------------------------------------------ *
     * Lifecycle
     * ------------------------------------------------------------------ */

    public function isPublished(): bool
    {
        return $this->status === ProductStatus::Published;
    }

    public function publish(): void
    {
        $this->forceFill([
            'status' => ProductStatus::Published,
            'status_reason' => null,
            'approved_at' => $this->approved_at ?? now(),
            'published_at' => $this->published_at ?? now(),
        ])->save();
    }

    public function submitForReview(): void
    {
        $this->forceFill([
            'status' => ProductStatus::PendingReview,
            'status_reason' => null,
            'submitted_at' => now(),
        ])->save();
    }

    public function reject(string $reason): void
    {
        $this->forceFill([
            'status' => ProductStatus::Rejected,
            'status_reason' => $reason,
        ])->save();
    }

    /** Increment the view counter without touching timestamps. */
    public function recordView(): void
    {
        static::withoutTimestamps(fn () => $this->increment('views_count'));
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