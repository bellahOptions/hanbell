<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Gives a model a public-facing uuid while keeping an auto-increment primary
 * key.
 *
 * Why not make the uuid the primary key: MySQL clusters on the primary key, so
 * random uuids fragment the index and make every foreign-key join more
 * expensive. Keeping a small integer key internally and exposing a uuid
 * publicly gets the privacy benefit without the storage and join cost.
 *
 * The uuid is also what public URL tokens are derived from (see HasUrlToken).
 *
 * @mixin Model
 */
trait HasUuid
{
    public static function bootHasUuid(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Route publicly by uuid, never by id.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Resolve a uuid outside of routing (e.g. from a token payload).
     */
    public static function findByUuid(string $uuid): ?static
    {
        return static::query()->where('uuid', $uuid)->first();
    }
}
