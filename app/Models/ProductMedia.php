<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductMedia extends Model
{
    use HasUuid;

    protected $fillable = [
        'product_id', 'variant_id', 'path', 'disk', 'alt_text', 'caption',
        'position', 'is_primary', 'credit_name', 'credit_url',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_primary' => 'boolean',
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

    /**
     * An absolute URL is stored as-is (Unsplash CDN); anything else is a path
     * on the configured disk.
     */
    public function url(): string
    {
        if (Str::startsWith($this->path, ['http://', 'https://', '//'])) {
            return $this->path;
        }

        return Storage::disk($this->disk ?: 'public')->url($this->path);
    }

    public function alt(): string
    {
        return $this->alt_text
            ?: ($this->product?->name ?? 'Product image');
    }

    public function isExternal(): bool
    {
        return Str::startsWith($this->path, ['http://', 'https://', '//']);
    }
}
