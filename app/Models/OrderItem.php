<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * An immutable snapshot of what was bought. Product name, variant, SKU, price
 * and commission are all copied at order time so editing or deleting a product
 * later never rewrites order history.
 */
class OrderItem extends Model
{
    use HasUuid;

    protected $fillable = [
        'order_id', 'vendor_order_id', 'vendor_id', 'product_id', 'variant_id',
        'product_name', 'variant_label', 'sku', 'vendor_name', 'image_path',
        'quantity', 'unit_price_minor', 'line_total_minor',
        'commission_minor', 'commission_percent',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_minor' => 'integer',
            'line_total_minor' => 'integer',
            'commission_minor' => 'integer',
            'commission_percent' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendorOrder(): BelongsTo
    {
        return $this->belongsTo(VendorOrder::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function formattedUnitPrice(): string
    {
        return Money::format($this->unit_price_minor, $this->order?->currency ?? 'NGN');
    }

    public function formattedLineTotal(): string
    {
        return Money::format($this->line_total_minor, $this->order?->currency ?? 'NGN');
    }

    public function imageUrl(): string
    {
        if (blank($this->image_path)) {
            return Product::placeholderImageUrl();
        }

        if (Str::startsWith($this->image_path, ['http://', 'https://', '//'])) {
            return $this->image_path;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    /** The product behind this line, if it still exists. */
    public function linkedProductUrl(): ?string
    {
        return $this->product?->isPublished() ? $this->product->publicUrl() : null;
    }
}
