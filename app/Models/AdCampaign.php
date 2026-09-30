<?php

namespace App\Models;

use App\Enums\AdAudience;
use App\Enums\AdCampaignStatus;
use App\Enums\AdDevice;
use App\Enums\AdPricingModel;
use App\Models\Concerns\HasUuid;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdCampaign extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'advertiser_id', 'vendor_id', 'name', 'description', 'status',
        'pricing_model', 'budget_minor', 'spend_minor', 'bid_minor',
        'starts_at', 'ends_at',
        'audience', 'device', 'target_department_ids', 'target_category_ids',
        'target_locales', 'target_genders',
        'max_impressions_per_session', 'daily_impression_cap', 'priority', 'is_exclusive',
    ];

    protected function casts(): array
    {
        return [
            'status' => AdCampaignStatus::class,
            'pricing_model' => AdPricingModel::class,
            'audience' => AdAudience::class,
            'device' => AdDevice::class,
            'budget_minor' => 'integer',
            'spend_minor' => 'integer',
            'bid_minor' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'target_department_ids' => 'array',
            'target_category_ids' => 'array',
            'target_locales' => 'array',
            'target_genders' => 'array',
            'max_impressions_per_session' => 'integer',
            'daily_impression_cap' => 'integer',
            'priority' => 'integer',
            'is_exclusive' => 'boolean',
            'impressions_count' => 'integer',
            'clicks_count' => 'integer',
            'conversions_count' => 'integer',
            'token_version' => 'integer',
        ];
    }

    /* ------------------------------------------------------------------ *
     * Relationships
     * ------------------------------------------------------------------ */

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(Advertiser::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creatives(): HasMany
    {
        return $this->hasMany(AdCreative::class);
    }

    public function activeCreatives(): HasMany
    {
        return $this->creatives()->where('is_active', true);
    }

    public function placements(): BelongsToMany
    {
        return $this->belongsToMany(AdPlacement::class, 'ad_campaign_placement')
            ->withPivot('weight')
            ->withTimestamps();
    }

    public function events(): HasMany
    {
        return $this->hasMany(AdEvent::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(AdDailyStat::class);
    }

    /* ------------------------------------------------------------------ *
     * Scopes
     * ------------------------------------------------------------------ */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AdCampaignStatus::Active);
    }

    /** Inside its flight window (an open-ended window is always inside). */
    public function scopeInFlight(Builder $query): Builder
    {
        $now = now();

        return $query->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    /**
     * Budget not yet exhausted. The OR must be grouped: left ungrouped it
     * would escape any surrounding WHERE and widen the whole query.
     */
    public function scopeUnderBudget(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereColumn('spend_minor', '<', 'budget_minor')
                ->orWhere('budget_minor', 0);
        });
    }

    /* ------------------------------------------------------------------ *
     * Delivery maths
     * ------------------------------------------------------------------ */

    public function remainingBudgetMinor(): int
    {
        return max(0, (int) $this->budget_minor - (int) $this->spend_minor);
    }

    public function isExhausted(): bool
    {
        return $this->budget_minor > 0 && $this->spend_minor >= $this->budget_minor;
    }

    public function budgetConsumedPercent(): int
    {
        if ($this->budget_minor <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->spend_minor / $this->budget_minor) * 100));
    }

    /**
     * Fraction of the flight elapsed, used for pacing.
     * Returns null when there is no scheduled window to pace against.
     */
    public function flightProgress(): ?float
    {
        if (! $this->starts_at || ! $this->ends_at) {
            return null;
        }

        $total = $this->ends_at->getTimestamp() - $this->starts_at->getTimestamp();

        if ($total <= 0) {
            return 1.0;
        }

        $elapsed = now()->getTimestamp() - $this->starts_at->getTimestamp();

        return max(0.0, min(1.0, $elapsed / $total));
    }

    public function isInFlight(): bool
    {
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        return ! ($this->ends_at && $this->ends_at->isPast());
    }

    public function isServable(): bool
    {
        return $this->status === AdCampaignStatus::Active
            && $this->isInFlight()
            && ! $this->isExhausted();
    }

    /* ------------------------------------------------------------------ *
     * Performance
     * ------------------------------------------------------------------ */

    public function clickThroughRate(): float
    {
        if ($this->impressions_count <= 0) {
            return 0.0;
        }

        return round(($this->clicks_count / $this->impressions_count) * 100, 2);
    }

    public function costPerClickMinor(): ?int
    {
        if ($this->clicks_count <= 0) {
            return null;
        }

        return (int) round($this->spend_minor / $this->clicks_count);
    }

    public function effectiveCpmMinor(): ?int
    {
        if ($this->impressions_count <= 0) {
            return null;
        }

        return (int) round(($this->spend_minor / $this->impressions_count) * 1000);
    }

    public function formattedBudget(): string
    {
        return Money::format($this->budget_minor);
    }

    public function formattedSpend(): string
    {
        return Money::format($this->spend_minor);
    }

    /**
     * The bid expressed per single impression, which is what the ranking
     * algorithm compares.
     */
    public function bidPerImpressionMinor(): float
    {
        return match ($this->pricing_model) {
            AdPricingModel::Cpm => $this->bid_minor / 1000,
            AdPricingModel::Cpc => $this->bid_minor * ($this->historicalCtr() ?: 0.01),
            AdPricingModel::Flat => $this->starts_at && $this->ends_at
                ? $this->bid_minor / max(1, $this->ends_at->diffInSeconds($this->starts_at) / 60) // per minute
                : 0.0,
        };
    }

    /** Blended CTR used to value a CPC bid as an impression value. */
    public function historicalCtr(): float
    {
        if ($this->impressions_count > 0) {
            return $this->clicks_count / $this->impressions_count;
        }

        return 0.01; // conservative prior for a campaign with no history yet
    }

    /** Targeting summary for the admin table. */
    public function targetingSummary(): string
    {
        $bits = [
            $this->audience instanceof AdAudience ? $this->audience->label() : (string) $this->audience,
            $this->device instanceof AdDevice ? $this->device->label() : (string) $this->device,
        ];

        if (! empty($this->target_locales)) {
            $bits[] = 'locales: '.implode(', ', $this->target_locales);
        }

        if (! empty($this->target_genders)) {
            $bits[] = implode('/', $this->target_genders);
        }

        return implode(' · ', array_filter($bits));
    }
}
