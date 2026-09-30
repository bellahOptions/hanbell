<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'product_id', 'name', 'sku', 'size', 'color', 'color_hex', 'material',
        'price_minor', 'compare_at_price_minor', 'position', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
            'compare_at_price_minor' => 'integer',
            'position' => 'integer',
            'is_active' => 'boolean',
            'token_version' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'variant_id');
    }

    /* ------------------------------------------------------------------ *
     * Pricing — a variant inherits the product price unless it overrides it.
     * ------------------------------------------------------------------ */

    public function effectivePrice(): int
    {
        return (int) ($this->price_minor ?? $this->product?->price_minor ?? 0);
    }

    public function formattedPrice(): string
    {
        return Money::format($this->effectivePrice(), $this->product?->currency ?? 'NGN');
    }

    /** Human label used on order lines and in the admin table. */
    public function label(): string
    {
        return $this->name ?: collect([$this->size, $this->color])->filter()->implode(' / ');
    }

    public function availableQuantity(): int
    {
        return $this->inventory?->available() ?? 0;
    }

    public function isInStock(): bool
    {
        return $this->availableQuantity() > 0 || $this->inventory?->allow_backorder === true;
    }

    /**
     * Resolve the variant a shopper means from a "size / colour" style key.
     */
    public static function findBySkuOrLabel(Product $product, string $key): ?self
    {
        return $product->variants()
            ->where(fn ($q) => $q->where('sku', $key)->orWhere('name', $key))
            ->first();
    }
}
