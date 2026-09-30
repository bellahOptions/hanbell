<?php

namespace App\Models;

use App\Enums\AdEventType;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One counted ad interaction.
 *
 * The visitor fingerprint is a SHA-256 of IP + user agent + date. The raw IP is
 * never written, so the table cannot be used to reconstruct a browsing history
 * even if it leaks.
 */
class AdEvent extends Model
{
    use HasUuid;

    protected $fillable = [
        'ad_campaign_id', 'ad_creative_id', 'ad_placement_id', 'event_type',
        'visitor_hash', 'user_id', 'is_authenticated', 'spend_minor',
        'device', 'locale', 'path', 'referrer', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => AdEventType::class,
            'is_authenticated' => 'boolean',
            'spend_minor' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    public function creative(): BelongsTo
    {
        return $this->belongsTo(AdCreative::class, 'ad_creative_id');
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(AdPlacement::class, 'ad_placement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOfType(Builder $query, AdEventType $type): Builder
    {
        return $query->where('event_type', $type);
    }

    public function scopeBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('occurred_at', [$from, $to]);
    }

    /**
     * The daily-rotating visitor fingerprint. Deliberately not reversible to an
     * IP address, and not stable across days, so it cannot be used for
     * long-term cross-site tracking.
     */
    public static function fingerprint(?string $ip, ?string $userAgent, ?string $date = null): string
    {
        $date ??= now()->toDateString();

        return hash('sha256', implode('|', [
            $ip ?: 'unknown',
            $userAgent ?: 'unknown',
            $date,
            (string) config('app.key'),
        ]));
    }
}
