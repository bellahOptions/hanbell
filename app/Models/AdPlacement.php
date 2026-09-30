<?php

namespace App\Models;

use App\Enums\AdPlacementKey;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * The registry row for one ad slot. Kept in sync from AdPlacementKey by
 * AdPlacementSeeder so slots are queryable and countable.
 */
class AdPlacement extends Model
{
    use HasUuid;

    protected $fillable = [
        'key', 'label', 'description', 'recommended_size', 'aspect_class',
        'max_creatives', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_creatives' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(AdCampaign::class, 'ad_campaign_placement')
            ->withPivot('weight')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** The enum case behind this row, when the key is still known. */
    public function placementKey(): ?AdPlacementKey
    {
        return AdPlacementKey::tryFrom($this->key);
    }

    public function maxCreatives(): int
    {
        return $this->placementKey()?->maxCreatives() ?? (int) $this->max_creatives;
    }

    public function aspectClass(): string
    {
        return $this->placementKey()?->aspectClass() ?? 'aspect-[4/1]';
    }
}
