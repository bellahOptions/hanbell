<?php

namespace App\Models;

use App\Models\Concerns\HasUrlToken;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdCreative extends Model
{
    use HasUrlToken;
    use HasUuid;

    protected $fillable = [
        'ad_campaign_id', 'name', 'headline', 'subheadline', 'cta_label',
        'image_path', 'image_url', 'mobile_image_path', 'background_color',
        'text_color', 'destination_url', 'destination_type', 'is_active', 'weight',
        'impressions_count', 'clicks_count', 'ctr_prior_alpha', 'ctr_prior_beta',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'weight' => 'integer',
            'impressions_count' => 'integer',
            'clicks_count' => 'integer',
            'ctr_prior_alpha' => 'decimal:3',
            'ctr_prior_beta' => 'decimal:3',
            'token_version' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AdEvent::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /* ------------------------------------------------------------------ *
     * Presentation
     * ------------------------------------------------------------------ */

    public function imageUrl(): ?string
    {
        if (filled($this->image_url)) {
            return $this->image_url;
        }

        if (blank($this->image_path)) {
            return null;
        }

        if (Str::startsWith($this->image_path, ['http://', 'https://', '//'])) {
            return $this->image_path;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    public function mobileImageUrl(): ?string
    {
        if (blank($this->mobile_image_path)) {
            return $this->imageUrl();
        }

        if (Str::startsWith($this->mobile_image_path, ['http://', 'https://', '//'])) {
            return $this->mobile_image_path;
        }

        return Storage::disk('public')->url($this->mobile_image_path);
    }

    public function hasImage(): bool
    {
        return filled($this->image_url) || filled($this->image_path);
    }

    public function clickThroughRate(): float
    {
        if ($this->impressions_count <= 0) {
            return 0.0;
        }

        return round(($this->clicks_count / $this->impressions_count) * 100, 2);
    }

    /**
     * The URL a click-through sends the visitor to.
     *
     * Always the internal tracking route — never the raw destination — so the
     * destination can be validated before the redirect happens.
     */
    public function trackingUrl(?int $placementId = null): string
    {
        return route('ads.click', [
            'creative' => $this->urlToken(),
            'placement' => $placementId,
        ]);
    }

    public function destinationLabel(): string
    {
        $host = parse_url((string) $this->destination_url, PHP_URL_HOST);

        return $host ?: Str::limit((string) $this->destination_url, 40);
    }

    /**
     * Record a click and update the Beta prior used for rotation.
     *
     * The prior starts at Beta(1, 99) — a 1% CTR assumption — so a creative
     * with no history is not unfairly favoured or buried, and it converges as
     * real data arrives.
     */
    public function recordClick(): void
    {
        $this->increment('clicks_count');
        $this->increment('ctr_prior_alpha');
        $this->refresh();
    }

    public function recordImpression(): void
    {
        $this->increment('impressions_count');
        $this->increment('ctr_prior_beta');
        $this->refresh();
    }

    public function recordConversion(): void
    {
        // Conversions count against the campaign, not the creative's CTR prior.
        $this->campaign?->increment('conversions_count');
    }

    /**
     * Route binding for this model.
     *
     * Declared here rather than left to the trait because Spatie's HasSlug also
     * defines resolveRouteBinding, and two traits providing the same method is a
     * fatal collision. Delegating to resolveTokenBinding() keeps the token
     * verification in one place. See App\Models\Concerns\HasUrlToken.
     */
    public function resolveRouteBinding($value, $field = null): ?\Illuminate\Database\Eloquent\Model
    {
        return $this->resolveTokenBinding($value);
    }
}