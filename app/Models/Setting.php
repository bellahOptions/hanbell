<?php

namespace App\Models;

use App\Support\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Read through App\Support\Settings rather than querying this model directly,
 * so memoisation and cache invalidation stay in one place.
 */
class Setting extends Model
{
    protected $fillable = [
        'group', 'key', 'value', 'type', 'label', 'description', 'is_public', 'position',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * Named ofGroup(), not group().
     *
     * `group` is both a column name and the argument, and a scope called
     * `group` makes `Setting::group('seo')` read ambiguously — it is easy to end
     * up passing the column value where the query builder belongs, which
     * surfaces as a baffling "no such column: seo.default_title" from a
     * completely different call site.
     */
    public function scopeOfGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group)->orderBy('position');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /** Cast the stored string according to this row's declared type. */
    public function typedValue(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'json' => json_decode((string) $this->value, true),
            default => $this->value,
        };
    }

    protected static function booted(): void
    {
        $flush = fn () => Settings::flush();

        static::saved($flush);
        static::deleted($flush);
    }
}
