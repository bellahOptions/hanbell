<?php

namespace App\Models\Concerns;

use App\Support\Tokens\InvalidUrlTokenException;
use App\Support\Tokens\UrlToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Public URLs for this model are opaque signed tokens, not ids or uuids.
 *
 * The token embeds this record's `token_version`; bumping it revokes every
 * previously issued link for the record in one UPDATE, without changing its
 * uuid. Binding verifies the signature for *this* model type, so a token
 * minted for one entity cannot be replayed against another route
 * (type confusion).
 *
 * Models using this trait must have `uuid` and `token_version` columns.
 *
 * @mixin Model
 */
trait HasUrlToken
{
    /**
     * The type string bound into tokens. Distinct per model, so tokens are not
     * interchangeable across routes.
     */
    public static function urlTokenType(): string
    {
        return str(class_basename(static::class))->snake()->toString();
    }

    /**
     * This record's current public token.
     */
    public function urlToken(?int $ttlDays = null): string
    {
        return UrlToken::encode(
            static::urlTokenType(),
            (string) $this->uuid,
            (int) ($this->token_version ?? 1),
            $ttlDays,
        );
    }

    /**
     * Resolve a route binding from a token, falling back to a plain uuid.
     *
     * Returning null (which the router turns into a 404) for anything that fails
     * both checks, so a tampered or expired link is indistinguishable from a
     * missing record.
     *
     * WHY THE UUID FALLBACK MATTERS: without it, this method would return null
     * for every ordinary uuid URL — including the ones `route()` itself
     * generates from a model, because HasUuid::getRouteKeyName() returns `uuid`.
     * Admin screens and any un-tokenised link would 404 with no obvious cause.
     *
     * NOTE ON THE NAME: this is deliberately not called `resolveRouteBinding`.
     * Spatie's HasSlug trait also defines `resolveRouteBinding`, and two traits
     * providing the same method on one model is a fatal collision even when the
     * signatures differ. Models therefore declare `resolveRouteBinding()`
     * themselves and delegate here.
     */
    public function resolveTokenBinding($value): ?Model
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (string) $value;

        // Preferred path: a signed token.
        try {
            $payload = UrlToken::decode($value, static::urlTokenType());

            return $this->resolveTokenPayload($payload);
        } catch (InvalidUrlTokenException) {
            // Not a usable token — fall through and treat it as a uuid.
        }

        // Fallback: a well-formed uuid. Anything else is a miss.
        if (! Str::isUuid($value)) {
            return null;
        }

        return static::query()->where('uuid', $value)->first();
    }

    /**
     * Find the record a verified token payload points at.
     *
     * The version inside the token must still match the record's live version —
     * a mismatch means the link was revoked, and is treated the same as a miss.
     *
     * @param  array{type:string,uuid:string,version:int,issued_at:int,expires_at:int}  $payload
     */
    protected function resolveTokenPayload(array $payload): ?Model
    {
        return static::query()
            ->where('uuid', $payload['uuid'])
            ->where('token_version', $payload['version'])
            ->first();
    }

    /**
     * Revoke every URL previously issued for this record.
     */
    public function rotateUrlToken(): void
    {
        $this->forceFill(['token_version' => ((int) ($this->token_version ?? 1)) + 1])->save();
    }

    /**
     * Scope to records whose token version matches, for building tokenised
     * links in bulk without a per-row query.
     */
    public function scopeWhereTokenValid(Builder $query, int $version): Builder
    {
        return $query->where('token_version', $version);
    }
}
