<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = 'inventories';

    protected $fillable = [
        'product_id', 'variant_id', 'quantity_on_hand', 'quantity_reserved',
        'low_stock_threshold', 'allow_backorder',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'integer',
            'quantity_reserved' => 'integer',
            'low_stock_threshold' => 'integer',
            'allow_backorder' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->latest();
    }

    /** Sellable quantity: what is physically there minus what is promised. */
    public function available(): int
    {
        return max(0, $this->quantity_on_hand - $this->quantity_reserved);
    }

    public function isLow(): bool
    {
        return $this->available() > 0 && $this->available() <= $this->low_stock_threshold;
    }

    public function isOut(): bool
    {
        return $this->available() <= 0;
    }

    /** Percentage of stock sold through, for the admin stock bar. */
    public function depletionPercent(): int
    {
        $total = $this->quantity_on_hand + $this->quantity_reserved;

        if ($total <= 0) {
            return 100;
        }

        return (int) round(($this->quantity_reserved / $total) * 100);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity_on_hand - quantity_reserved <= low_stock_threshold');
    }

    public function scopeOutOfStock($query)
    {
        return $query->whereRaw('quantity_on_hand - quantity_reserved <= 0');
    }
}
