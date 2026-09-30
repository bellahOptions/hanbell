<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Database-backed key/value settings, editable by the administrator.
 *
 * Values are memoised per request and cached across requests, and the whole
 * layer degrades to config defaults when the table is missing (before the
 * first migration, or during an early install).
 */
final class Settings
{
    /** @var array<string, mixed>|null */
    private static ?array $memo = null;

    private const CACHE_KEY = 'hanbell.settings.all';

    private const CACHE_TTL = 3600;

    /**
     * Read a setting, falling back to config when unset.
     *
     * Keys are namespaced as "group.key" (e.g. "seo.default_description"),
     * and the config fallback uses the same path, so a missing row is
     * transparent.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return config('hanbell.'.$key, $default);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        return (int) self::get($key, $default);
    }

    /**
     * All settings as a flat key => value map.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        if (! self::tableExists()) {
            return self::$memo = [];
        }

        try {
            /*
             * Cached as a plain array, not a Collection. A Collection of
             * stdClass rows round-trips badly through some cache stores (the
             * file store in particular unserializes the objects before the
             * class map is ready), which surfaces as
             * "tried to access a property on an incomplete object". Arrays have
             * no such problem.
             *
             * In tests the cache is skipped entirely: each test runs against a
             * fresh in-memory database, so a cross-request cache can only ever
             * serve another test's rows — and with the file store it also means
             * writing to disk on every test, which is both slower and a source
             * of flaky permission errors on Windows.
             */
            $rows = app()->runningUnitTests()
                ? DB::table('settings')->get(['key', 'value', 'type'])->map(fn ($row) => (array) $row)->all()
                : Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
                    return DB::table('settings')
                        ->get(['key', 'value', 'type'])
                        ->map(fn ($row) => (array) $row)
                        ->all();
                });
        } catch (Throwable) {
            return self::$memo = [];
        }

        $values = [];

        foreach ($rows as $row) {
            $row = (array) $row;
            $values[$row['key']] = self::cast($row['value'], $row['type']);
        }

        return self::$memo = $values;
    }

    /**
     * Persist a batch of settings and bust the cache.
     *
     * @param  array<string, mixed>  $values
     */
    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $type = is_bool($value) ? 'boolean' : (is_array($value) ? 'json' : (is_int($value) ? 'integer' : 'string'));

            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? '1' : '0') : (string) $value),
                    'type' => $type,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        self::flush();
    }

    public static function forget(string $key): void
    {
        DB::table('settings')->where('key', $key)->delete();
        self::flush();
    }

    /**
     * Drop the memo and the cross-request cache. Call after any write.
     */
    public static function flush(): void
    {
        self::$memo = null;
        Cache::forget(self::CACHE_KEY);
    }

    /* ------------------------------------------------------------------ */

    private static function cast(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    private static function tableExists(): bool
    {
        static $exists = null;

        if ($exists !== null) {
            return $exists;
        }

        try {
            return $exists = Schema::hasTable('settings');
        } catch (Throwable) {
            return $exists = false;
        }
    }
}
