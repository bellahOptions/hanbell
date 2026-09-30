<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasUuid;

    protected $fillable = ['cart_id', 'product_id', 'variant_id', 'vendor_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * The price this line should be charged at, read live.
     *
     * Deliberately not a column: storing it would let a price change (or a
     * manipulated request) decide what the customer pays.
     */
    public function unitPriceMinor(): int
    {
        return (int) ($this->product?->priceFor($this->variant) ?? 0);
    }

    public function lineTotalMinor(): int
    {
        return $this->unitPriceMinor() * $this->quantity;
    }

    public function isAvailable(): bool
    {
        return $this->product?->isPublished() === true;
    }
}
