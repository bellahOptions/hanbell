<?php

namespace App\Models;

use App\Enums\InventoryMovementReason;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    use HasUuid;

    protected $fillable = [
        'inventory_id', 'product_id', 'variant_id', 'reason', 'quantity_change',
        'quantity_after', 'reference_type', 'reference_id', 'user_id', 'note',
    ];

    protected function casts(): array
    {
        return [
            'reason' => InventoryMovementReason::class,
            'quantity_change' => 'integer',
            'quantity_after' => 'integer',
        ];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The order (or other document) that caused this movement. */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function isIncrease(): bool
    {
        return $this->quantity_change > 0;
    }
}
