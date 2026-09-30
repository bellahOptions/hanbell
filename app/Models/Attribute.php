<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Attribute extends Model
{
    use HasUuid;

    protected $fillable = [
        'name', 'slug', 'type', 'is_filterable', 'is_variant_axis', 'position',
    ];

    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
            'is_variant_axis' => 'boolean',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $attribute): void {
            $attribute->slug ??= Str::slug($attribute->name);
        });
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('position');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'attribute_product')
            ->withPivot('attribute_value_id')
            ->withTimestamps();
    }

    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }
}
