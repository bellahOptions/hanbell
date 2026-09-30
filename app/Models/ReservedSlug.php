<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Slugs that generated content may never claim, so a product or page can never
 * shadow a real application route.
 */
class ReservedSlug extends Model
{
    protected $fillable = ['slug', 'reason'];

    public static function isReserved(string $slug): bool
    {
        return static::query()->where('slug', strtolower(trim($slug)))->exists();
    }
}
