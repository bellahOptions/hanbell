<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $fillable = ['from_path', 'to_path', 'status_code', 'hits', 'is_active'];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'hits' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Look up a redirect for a request path, normalised to a leading slash and
     * no trailing slash so "/old/" and "old" match the same row.
     */
    public static function matchFor(string $path): ?self
    {
        $normalised = '/'.trim($path, '/');

        return static::query()
            ->active()
            ->whereIn('from_path', array_unique([$normalised, rtrim($normalised, '/') ?: '/']))
            ->first();
    }

    public function recordHit(): void
    {
        static::withoutTimestamps(fn () => $this->increment('hits'));
    }
}
