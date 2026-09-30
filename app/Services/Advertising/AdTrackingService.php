<?php

namespace App\Services\Advertising;

use App\Enums\AdEventType;
use App\Enums\AdPricingModel;
use App\Models\AdCampaign;
use App\Models\AdCreative;
use App\Models\AdEvent;
use App\Models\AdPlacement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Records ad events and keeps the denormalised counters in step.
 *
 * Two things this service is careful about:
 *
 *  1. It never stores a raw IP address. The visitor fingerprint is a
 *     daily-rotating hash (see AdEvent::fingerprint), so the events table
 *     cannot be turned back into a browsing history.
 *
 *  2. Impressions are deduplicated inside a short window per
 *     visitor/campaign/slot, but clicks never are. A user re-seeing the same ad
 *     after a page refresh should not generate a second billable impression; a
 *     user clicking twice genuinely did click twice.
 *
 * The counters on `ad_campaigns` and `ad_creatives` exist so the serving path
 * can check budget and pacing without aggregating the unbounded events table.
 */
class AdTrackingService
{
    public function __construct(private readonly AdServer $adServer) {}

    /**
     * Record a viewable impression.
     *
     * @return bool whether the impression was counted (false when deduped)
     */
    public function recordImpression(
        AdCreative $creative,
        ?AdPlacement $placement = null,
        ?string $ip = null,
        ?string $userAgent = null,
        ?int $userId = null,
    ): bool {
        $campaign = $creative->campaign;

        if (! $campaign) {
            return false;
        }

        $visitorHash = AdEvent::fingerprint($ip, $userAgent);

        // Collapse repeat impressions for the same visitor/campaign/slot inside
        // the dedupe window. Cache::add is atomic, so two concurrent requests
        // cannot both win.
        $window = AdEventType::Impression->dedupeWindowMinutes();

        $dedupeKey = sprintf(
            'ad:imp:%s:%s:%s',
            $visitorHash,
            $campaign->id,
            $placement?->id ?? 'none',
        );

        if (! Cache::add($dedupeKey, 1, now()->addMinutes($window))) {
            return false;
        }

        $spend = $this->spendForImpression($campaign);

        DB::transaction(function () use ($creative, $campaign, $placement, $visitorHash, $userAgent, $userId, $spend): void {
            AdEvent::create([
                'ad_campaign_id' => $campaign->id,
                'ad_creative_id' => $creative->id,
                'ad_placement_id' => $placement?->id,
                'event_type' => AdEventType::Impression,
                'visitor_hash' => $visitorHash,
                'user_id' => $userId,
                'is_authenticated' => $userId !== null,
                'spend_minor' => $spend,
                'device' => $this->adServer->detectDevice((string) $userAgent),
                'locale' => app()->getLocale(),
                'path' => request()?->path(),
                'referrer' => request()?->headers->get('referer'),
                'occurred_at' => now(),
            ]);

            $creative->increment('impressions_count');
            $creative->increment('ctr_prior_beta');

            $campaign->increment('impressions_count');

            if ($spend > 0) {
                $campaign->increment('spend_minor', $spend);
            }

            // A campaign that just exhausted its budget stops being served.
            $campaign->refresh();

            if ($campaign->isExhausted() && $campaign->status->value === 'active') {
                $campaign->forceFill(['status' => \App\Enums\AdCampaignStatus::Completed])->save();
            }
        });

        return true;
    }

    /**
     * Record a click. Never deduplicated — a second click is a second click.
     */
    public function recordClick(
        AdCreative $creative,
        ?AdPlacement $placement = null,
        ?string $ip = null,
        ?string $userAgent = null,
        ?int $userId = null,
    ): void {
        $campaign = $creative->campaign;

        if (! $campaign) {
            return;
        }

        $spend = $this->spendForClick($campaign);

        DB::transaction(function () use ($creative, $campaign, $placement, $ip, $userAgent, $userId, $spend): void {
            AdEvent::create([
                'ad_campaign_id' => $campaign->id,
                'ad_creative_id' => $creative->id,
                'ad_placement_id' => $placement?->id,
                'event_type' => AdEventType::Click,
                'visitor_hash' => AdEvent::fingerprint($ip, $userAgent),
                'user_id' => $userId,
                'is_authenticated' => $userId !== null,
                'spend_minor' => $spend,
                'locale' => app()->getLocale(),
                'path' => request()?->path(),
                'referrer' => request()?->headers->get('referer'),
                'occurred_at' => now(),
            ]);

            $creative->increment('clicks_count');
            // Reinforce the Beta prior: a click is evidence of a good creative.
            $creative->increment('ctr_prior_alpha');

            $campaign->increment('clicks_count');

            if ($spend > 0) {
                $campaign->increment('spend_minor', $spend);
            }
        });
    }

    /**
     * Record a conversion attributed to a creative.
     */
    public function recordConversion(AdCreative $creative, int $revenueMinor = 0): void
    {
        $campaign = $creative->campaign;

        if (! $campaign) {
            return;
        }

        AdEvent::create([
            'ad_campaign_id' => $campaign->id,
            'ad_creative_id' => $creative->id,
            'event_type' => AdEventType::Conversion,
            'spend_minor' => 0,
            'locale' => app()->getLocale(),
            'occurred_at' => now(),
        ]);

        $campaign->increment('conversions_count');
    }

    /* ------------------------------------------------------------------ *
     * Costing
     * ------------------------------------------------------------------ */

    /**
     * What one impression costs.
     *
     * CPM bids are per thousand, so a single impression is a thousandth of the
     * bid. CPC campaigns cost nothing to show — the money is charged on click.
     * Flat-fee campaigns are invoiced for the whole flight, so an impression
     * carries no incremental cost.
     */
    public function spendForImpression(AdCampaign $campaign): int
    {
        return match ($campaign->pricing_model) {
            AdPricingModel::Cpm => (int) round($campaign->bid_minor / 1000),
            AdPricingModel::Cpc, AdPricingModel::Flat => 0,
        };
    }

    public function spendForClick(AdCampaign $campaign): int
    {
        return match ($campaign->pricing_model) {
            AdPricingModel::Cpc => (int) $campaign->bid_minor,
            AdPricingModel::Cpm, AdPricingModel::Flat => 0,
        };
    }

    /**
     * Credit a set of campaigns with a conversion for reporting purposes.
     *
     * This is reporting only — `spend_minor` is never touched here, because
     * attribution is last-touch and coarse and must not decide what an
     * advertiser is billed. Only verified impressions and clicks move money.
     *
     * @param  array<int,int>  $campaignIds
     */
    public function attributeOrderToCampaigns(int $orderId, int $revenueMinor, array $campaignIds): void
    {
        $ids = array_values(array_unique(array_filter($campaignIds)));

        if ($ids === []) {
            return;
        }

        AdCampaign::whereIn('id', $ids)->each(
            fn (AdCampaign $campaign) => $campaign->increment('conversions_count')
        );
    }
}
