<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Daily rollup of ad delivery. The serving algorithm and every admin chart read
 * these rows so hot paths never aggregate the unbounded ad_events table.
 */
class AdDailyStat extends Model
{
    protected $fillable = [
        'ad_campaign_id', 'ad_creative_id', 'date', 'impressions', 'clicks',
        'conversions', 'unique_visitors', 'spend_minor', 'revenue_minor',
    ];

    protected function casts(): array
    {
        return [
            'impressions' => 'integer',
            'clicks' => 'integer',
            'conversions' => 'integer',
            'unique_visitors' => 'integer',
            'spend_minor' => 'integer',
            'revenue_minor' => 'integer',
        ];
    }

    /**
     * `date` is intentionally NOT cast to a date.
     *
     * A `date` cast makes Eloquent serialise the column as `Y-m-d 00:00:00`
     * when building a WHERE clause, which never matches a stored DATE value,
     * so the aggregation upsert misses the existing row and then dies on the
     * unique index. Normalising by hand in both directions is the fix; MySQL
     * would truncate a datetime on insert but SQLite (used by the test suite)
     * would not, so the model must be the one that is careful.
     */
    public function setDateAttribute(mixed $value): void
    {
        $this->attributes['date'] = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : Carbon::parse((string) $value)->format('Y-m-d');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    public function creative(): BelongsTo
    {
        return $this->belongsTo(AdCreative::class, 'ad_creative_id');
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->where('date', Carbon::parse($date)->format('Y-m-d'));
    }

    public function scopeBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('date', [
            Carbon::parse($from)->format('Y-m-d'),
            Carbon::parse($to)->format('Y-m-d'),
        ]);
    }

    public function clickThroughRate(): float
    {
        if ($this->impressions <= 0) {
            return 0.0;
        }

        return round(($this->clicks / $this->impressions) * 100, 2);
    }

    public function costPerClickMinor(): ?int
    {
        return $this->clicks > 0 ? (int) round($this->spend_minor / $this->clicks) : null;
    }

    public function costPerMilleMinor(): ?int
    {
        return $this->impressions > 0 ? (int) round(($this->spend_minor / $this->impressions) * 1000) : null;
    }
}
