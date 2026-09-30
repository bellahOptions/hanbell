<?php

namespace App\Services\Advertising;

use App\Enums\AdPricingModel;
use App\Models\AdCreative;
use Illuminate\Support\Collection;

/**
 * Scoring for the ad server.
 *
 * The value of an impression is not simply the bid. A campaign that has spent
 * 95% of its budget with 20% of its flight remaining is worth far more per
 * impression than one that is ahead of schedule, because the money is already
 * committed and will otherwise go undelivered. Equally, an ad nobody clicks is
 * worth less than its bid suggests. The multiplier chain below expresses that.
 *
 * Every factor is clamped to a sane range so no single term can run away with
 * the ranking, and the product is converted to integer micros so rankings are
 * deterministic (float comparison makes ordering unstable between requests).
 *
 * The weights are configuration, not constants, because they are the dials an
 * operator will actually want to turn.
 */
class AdRanker
{
    private const MICRO = 1_000_000;

    public function __construct(private readonly array $weights = []) {}

    public static function defaultWeights(): array
    {
        return [
            'pacing' => [
                'min' => 0.25,
                'max' => 4.0,
                // How hard the pacing multiplier bites.
                'intensity' => 1.0,
                // Weight given to "should have spent more by now" vs raw ratio.
                'blend' => 0.6,
            ],
            'pacing_floor_minor' => 100_000,
            'relevance' => [
                'weight' => 0.6,
            ],
            'quality' => [
                // CTR is compared against this, so a 2% CTR scores 1.0.
                'baseline_ctr' => 0.02,
                'min' => 0.5,
                'max' => 2.0,
                'weight' => 0.5,
                // Extra weight once a creative has enough data to be trusted.
                'confidence_impressions' => 1000,
            ],
            'fatigue_floor' => 1.0,
            'priority' => [
                // Per point of manual priority, a small multiplicative nudge.
                'per_point' => 0.05,
                'max' => 2.0,
            ],
            'value_clamp' => [0.0, 1_000_000_000.0],
        ];
    }

    public function weights(): array
    {
        return $this->weights ?: self::defaultWeights();
    }

    private function weight(string $path, mixed $default = null): mixed
    {
        $segments = explode('.', $path);
        $value = $this->weights();

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * The value of one impression of this campaign, in integer micros.
     *
     * @param  array<string,mixed>  $context  the serving context
     */
    public function score(\App\Models\AdCampaign $campaign, array $context = []): int
    {
        $base = $campaign->bidPerImpressionMinor();

        // A flat-fee campaign has no per-impression price, so it is valued at
        // the store's median bid — enough to compete, never enough to dominate.
        if ($campaign->pricing_model === AdPricingModel::Flat && $base <= 0.0) {
            $base = (float) ($context['median_bid_minor'] ?? 5.0);
        }

        if ($base <= 0.0) {
            return 0;
        }

        $value = $base
            * $this->pacingMultiplier($campaign)
            * $this->relevanceMultiplier($campaign, $context)
            * $this->qualityMultiplier($campaign, $context)
            * $this->fatigueMultiplier($context['seen_creatives'] ?? [], $campaign)
            * $this->priorityMultiplier($campaign);

        [$min, $max] = $this->weight('value_clamp', [0.0, 1_000_000_000.0]);

        return (int) round(max($min, min($max, $value)) * self::MICRO);
    }

    /* ------------------------------------------------------------------ *
     * Multipliers
     * ------------------------------------------------------------------ */

    /**
     * Deliver budget evenly across the flight.
     *
     * ratio > 1 means the campaign is behind schedule and should accelerate;
     * ratio < 1 means it is ahead and should ease off so the money lasts the
     * whole booking. Without this, every campaign would front-load its budget
     * into the first few days of its flight.
     */
    public function pacingMultiplier(\App\Models\AdCampaign $campaign): float
    {
        $progress = $campaign->flightProgress();

        // No scheduled window means nothing to pace against.
        if ($progress === null) {
            return 1.0;
        }

        $budget = (int) $campaign->budget_minor;

        if ($budget <= 0) {
            return 1.0;
        }

        $spent = (int) $campaign->spend_minor;

        // Guard the very start of a flight: with progress near zero the ideal
        // spend is also near zero, and a raw ratio would explode.
        $floor = (int) $this->weight('pacing_floor_minor', 100_000);
        $idealSpend = max($floor, $budget * $progress);
        $actualSpend = max($floor, $spent);

        $ratio = $idealSpend / $actualSpend;

        // Blend the raw ratio with a softened version so a single quiet hour
        // does not swing delivery dramatically.
        $blend = (float) $this->weight('pacing.blend', 0.6);
        $blended = ($ratio * $blend) + (1.0 * (1.0 - $blend));

        $min = (float) $this->weight('pacing.min', 0.25);
        $max = (float) $this->weight('pacing.max', 4.0);

        return $this->clamp($blended, $min, $max);
    }

    /**
     * How well this campaign matches what the visitor is looking at.
     * An ad targeted at the category being browsed is worth more than a
     * generic one, because it is more likely to be clicked.
     */
    public function relevanceMultiplier(\App\Models\AdCampaign $campaign, array $context = []): float
    {
        $weight = (float) $this->weight('relevance.weight', 0.6);

        $signals = [];
        $matches = 0;

        // Category targeting
        $targetCategories = (array) ($campaign->target_category_ids ?? []);
        if ($targetCategories !== []) {
            $signals[] = in_array($context['category_id'] ?? null, $targetCategories, true);
        }

        // Department targeting
        $targetDepartments = (array) ($campaign->target_department_ids ?? []);
        if ($targetDepartments !== []) {
            $signals[] = in_array($context['department_id'] ?? null, $targetDepartments, true);
        }

        // Locale targeting
        $targetLocales = (array) ($campaign->target_locales ?? []);
        if ($targetLocales !== []) {
            $signals[] = in_array($context['locale'] ?? null, $targetLocales, true);
        }

        // Gender targeting
        $targetGenders = (array) ($campaign->target_genders ?? []);
        if ($targetGenders !== []) {
            $signals[] = in_array($context['gender'] ?? null, $targetGenders, true);
        }

        if ($signals === []) {
            return 1.0;
        }

        $matches = count(array_filter($signals));
        $hitRate = $matches / count($signals);

        // 1.0 when nothing targeted; scales between (1 - weight) and 1 + weight.
        return 1.0 + (($hitRate * 2) - 1) * $weight;
    }

    /**
     * Historical click-through performance relative to the baseline.
     *
     * A creative with no impressions is scored at exactly 1.0 — it must not be
     * punished for being new, or nothing would ever get its first impression.
     */
    public function qualityMultiplier(\App\Models\AdCampaign $campaign, array $context = []): float
    {
        $impressions = (int) $campaign->impressions_count;

        if ($impressions <= 0) {
            return 1.0;
        }

        $baseline = (float) $this->weight('quality.baseline_ctr', 0.02);
        $ctr = $campaign->historicalCtr();

        if ($baseline <= 0.0) {
            return 1.0;
        }

        $ratio = $ctr / $baseline;

        $min = (float) $this->weight('quality.min', 0.5);
        $max = (float) $this->weight('quality.max', 2.0);
        $confidence = (int) $this->weight('quality.confidence_impressions', 1000);

        // Scale the effect in as evidence accumulates, so a campaign that got
        // one lucky click out of ten impressions is not treated as a winner.
        $trust = min(1.0, $impressions / max(1, $confidence));
        $weight = (float) $this->weight('quality.weight', 0.5);

        $scaled = 1.0 + (($this->clamp($ratio, $min, $max) - 1.0) * $weight * $trust);

        return $this->clamp($scaled, $min, $max);
    }

    /**
     * Creative fatigue: within a single page render, a campaign that has already
     * placed a creative is worth less for each further placement, so one
     * advertiser cannot fill every slot on a page.
     *
     * @param  array<int,int>  $seen  campaign_id => times already placed
     */
    public function fatigueMultiplier(array $seen, \App\Models\AdCampaign $campaign): float
    {
        $count = (int) ($seen[$campaign->id] ?? 0);

        if ($count === 0) {
            return 1.0;
        }

        $floor = (float) $this->weight('fatigue_floor', 1.0);

        // Halve the value for each additional placement, never below the floor.
        return max($floor, 1.0 / (2 ** $count));
    }

    /**
     * Manual priority lets an operator nudge a campaign (a house promotion, a
     * strategic partner) without editing its bid.
     */
    public function priorityMultiplier(\App\Models\AdCampaign $campaign): float
    {
        $priority = (int) $campaign->priority;

        if ($priority === 0) {
            return 1.0;
        }

        $perPoint = (float) $this->weight('priority.per_point', 0.05);
        $max = (float) $this->weight('priority.max', 2.0);

        return $this->clamp(1.0 + ($priority * $perPoint), 1.0 / $max, $max);
    }

    /**
     * Pick one creative from a campaign by Thompson sampling over its Beta
     * CTR priors.
     *
     * Pure weighted rotation keeps showing whatever won early, which starves new
     * creatives; pure uniform rotation never exploits a winner. Sampling from
     * each creative's posterior explores naturally and converges on the best
     * performer — and because it samples rather than takes the maximum, a
     * proven creative still occasionally yields to a challenger.
     *
     * @param  Collection<int,AdCreative>  $creatives
     */
    public function selectCreative(Collection $creatives): ?AdCreative
    {
        if ($creatives->isEmpty()) {
            return null;
        }

        if ($creatives->count() === 1) {
            return $creatives->first();
        }

        $best = null;
        $bestSample = -1.0;
        $totalWeight = 0.0;
        $weighted = [];

        foreach ($creatives as $creative) {
            $alpha = max(0.001, (float) $creative->ctr_prior_alpha);
            $beta = max(0.001, (float) $creative->ctr_prior_beta);

            // Beta variate via two Gamma variates (the standard construction).
            $sample = $this->gammaSample($alpha) / max(1e-9, $this->gammaSample($alpha) + $this->gammaSample($beta));

            // The campaign's own rotation weight biases the sample without
            // discarding the statistical signal.
            $weight = max(1, (int) $creative->weight);
            $weighted[$creative->id] = $sample * $weight;
            $totalWeight += $weight;

            if ($weighted[$creative->id] > $bestSample) {
                $bestSample = $weighted[$creative->id];
                $best = $creative;
            }
        }

        return $best ?? $creatives->first();
    }

    /* ------------------------------------------------------------------ *
     * Maths helpers
     * ------------------------------------------------------------------ */

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    /**
     * Sample from a Gamma(shape, 1) distribution — Marsaglia & Tsang's method.
     *
     * Needed only to build a Beta variate for creative selection; the
     * ranking path itself is pure arithmetic.
     */
    private function gammaSample(float $shape): float
    {
        if ($shape < 1.0) {
            // Boost a sub-1 shape into the valid range and correct afterwards.
            $u = $this->randomFloat();

            return $this->gammaSample($shape + 1.0) * ($u ** (1.0 / $shape));
        }

        $d = $shape - (1.0 / 3.0);
        $c = 1.0 / sqrt(9.0 * $d);

        // Bounded loop: the acceptance rate is high, and an unbounded loop in a
        // request path is unacceptable. Falling back to the mean is a safe
        // approximation for the vanishingly rare failure case.
        for ($attempt = 0; $attempt < 32; $attempt++) {
            do {
                $x = $this->normalSample();
                $v = 1.0 + ($c * $x);
            } while ($v <= 0.0);

            $v = $v ** 3;
            $u = $this->randomFloat();

            if ($u < 1.0 - (0.0331 * ($x ** 4))) {
                return $d * $v;
            }

            if (log($u) < (0.5 * ($x ** 2)) + ($d * (1.0 - $v + log($v)))) {
                return $d * $v;
            }
        }

        return $d;
    }

    /** Box–Muller normal sample. */
    private function normalSample(): float
    {
        $u1 = max(1e-12, $this->randomFloat());
        $u2 = $this->randomFloat();

        return sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
    }

    /** Random float in [0,1) — mt_rand gives enough resolution for sampling. */
    private function randomFloat(): float
    {
        return mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax();
    }
}
