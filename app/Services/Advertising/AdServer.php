<?php

namespace App\Services\Advertising;

use App\Enums\AdAudience;
use App\Enums\AdDevice;
use App\Models\AdCampaign;
use App\Models\AdCreative;
use App\Models\AdPlacement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves what a given ad slot shows for the current visitor.
 *
 * Pipeline: fetch candidates for the placement → drop those that are not
 * eligible for *this* visitor → score the remainder → take the best → pick a
 * creative by Thompson sampling.
 *
 * Statefulness matters: one instance is kept per request (registered as a
 * singleton) so `$seenCampaigns` accumulates across every slot on the page. That
 * is what stops a single advertiser owning all nine homepage slots, and it is
 * also how a slot that appears twice in one render still shows variety.
 */
class AdServer
{
    /** campaign id => number of creatives already placed in this request */
    private array $seenCampaigns = [];

    /** Slot results are memoised per placement so a repeated slot is consistent. */
    private array $resolved = [];

    private ?array $visitorContext = null;

    public function __construct(private readonly AdRanker $ranker) {}

    /**
     * Fill one slot.
     *
     * @return Collection<int,AdCreative>
     */
    public function fill(string|\App\Enums\AdPlacementKey $placement, ?int $limit = null, array $overrides = []): Collection
    {
        $key = $placement instanceof \App\Enums\AdPlacementKey ? $placement->value : (string) $placement;

        $placementModel = AdPlacement::where('key', $key)->first();

        if (! $placementModel || ! $placementModel->is_active) {
            return collect();
        }

        $max = $limit ?? $placementModel->maxCreatives();

        // Memoise: the same slot rendered twice on one page shows the same ads,
        // which is both cheaper and less jarring than reshuffling mid-page.
        $memoKey = $key.':'.$max;

        if (! isset($overrides['fresh']) && isset($this->resolved[$memoKey])) {
            return $this->resolved[$memoKey];
        }

        $context = $this->context($overrides);

        $candidates = AdCampaign::query()
            ->whereHas('placements', fn ($q) => $q->where('ad_placements.id', $placementModel->id))
            ->with(['creatives' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $eligible = $candidates->filter(fn (AdCampaign $campaign) => $this->isEligible($campaign, $context));

        if ($eligible->isEmpty()) {
            return $this->resolved[$memoKey] = collect();
        }

        // An exclusive campaign takes the whole slot if it is eligible.
        $exclusive = $eligible->firstWhere('is_exclusive', true);

        if ($exclusive) {
            $creative = $this->ranker->selectCreative($exclusive->creatives);

            if ($creative) {
                $this->markSeen($exclusive);

                return $this->resolved[$memoKey] = collect([$creative]);
            }
        }

        // Give the ranker a reference point for flat-fee campaigns with no bid.
        $context['median_bid_minor'] = $this->medianBid($eligible);

        $ranked = $eligible
            ->map(fn (AdCampaign $campaign) => [
                'campaign' => $campaign,
                'score' => $this->ranker->score($campaign, $context + ['seen_creatives' => $this->seenCampaigns]),
            ])
            ->filter(fn (array $row) => $row['score'] > 0)
            ->sortByDesc('score')
            ->values();

        $chosen = collect();

        foreach ($ranked as $row) {
            if ($chosen->count() >= $max) {
                break;
            }

            /** @var AdCampaign $campaign */
            $campaign = $row['campaign'];

            $creative = $this->ranker->selectCreative($campaign->creatives);

            if (! $creative) {
                continue;
            }

            // Refuse to place the same creative twice in one page.
            if ($chosen->contains(fn (AdCreative $c) => $c->id === $creative->id)) {
                continue;
            }

            $this->markSeen($campaign);
            $chosen->push($creative);
        }

        return $this->resolved[$memoKey] = $chosen;
    }

    /**
     * Whether a single campaign may be shown to this visitor right now.
     */
    public function isEligible(AdCampaign $campaign, ?array $context = null): bool
    {
        $context ??= $this->context();

        // Status, flight window and budget ceiling.
        if (! $campaign->isServable()) {
            return false;
        }

        if ($campaign->creatives->isEmpty()) {
            return false;
        }

        // Audience.
        if (! $this->matchesAudience($campaign, $context)) {
            return false;
        }

        // Device.
        if (! $this->matchesDevice($campaign, $context['device'] ?? null)) {
            return false;
        }

        // A vendor must never advertise on a competitor's own pages.
        if (! $this->matchesVendorContext($campaign, $context)) {
            return false;
        }

        // Locale / gender / category targeting is a hard filter; relevance only
        // re-weights campaigns that already pass.
        if (! $this->matchesHardTargeting($campaign, $context)) {
            return false;
        }

        // Daily delivery cap, read from the rollup rather than the events table.
        if ($campaign->daily_impression_cap) {
            $today = Cache::remember(
                'ad:daily:'.$campaign->id.':'.now()->toDateString(),
                300,
                fn () => (int) \App\Models\AdDailyStat::query()
                    ->where('ad_campaign_id', $campaign->id)
                    ->where('date', now()->toDateString())
                    ->sum('impressions'),
            );

            if ($today >= $campaign->daily_impression_cap) {
                return false;
            }
        }

        return true;
    }

    /* ------------------------------------------------------------------ *
     * Eligibility rules
     * ------------------------------------------------------------------ */

    private function matchesAudience(AdCampaign $campaign, array $context): bool
    {
        $audience = $campaign->audience instanceof AdAudience
            ? $campaign->audience
            : AdAudience::tryFrom((string) $campaign->audience) ?? AdAudience::Everyone;

        $isAuthenticated = (bool) ($context['is_authenticated'] ?? false);
        $hasPaidOrder = (bool) ($context['has_paid_order'] ?? false);

        return match ($audience) {
            AdAudience::Everyone => true,
            AdAudience::Guests => ! $isAuthenticated,
            AdAudience::SignedIn => $isAuthenticated,
            AdAudience::NewCustomers => ! $hasPaidOrder,
            AdAudience::ReturningCustomers => $hasPaidOrder,
        };
    }

    private function matchesDevice(AdCampaign $campaign, ?string $device): bool
    {
        $target = $campaign->device instanceof AdDevice
            ? $campaign->device
            : AdDevice::tryFrom((string) $campaign->device) ?? AdDevice::All;

        if ($target === AdDevice::All) {
            return true;
        }

        return $device === $target->value;
    }

    /**
     * A vendor's own ad must not appear on a rival's storefront page. Showing a
     * competitor's banner on a vendor's product page is both unfair to the
     * vendor and commercially wrong.
     */
    private function matchesVendorContext(AdCampaign $campaign, array $context): bool
    {
        $pageVendorId = $context['vendor_id'] ?? null;

        if ($pageVendorId === null) {
            return true;
        }

        $adVendorId = $campaign->vendor_id;

        // House campaigns (no vendor) are allowed anywhere.
        if ($adVendorId === null) {
            return true;
        }

        return (int) $adVendorId === (int) $pageVendorId;
    }

    private function matchesHardTargeting(AdCampaign $campaign, array $context): bool
    {
        if (($locales = (array) ($campaign->target_locales ?? [])) !== []) {
            if (! in_array($context['locale'] ?? null, $locales, true)) {
                return false;
            }
        }

        if (($genders = (array) ($campaign->target_genders ?? [])) !== []) {
            // A page with no gender context (homepage, cart) is not excluded by
            // gender targeting — there is nothing to contradict it.
            $contextGender = $context['gender'] ?? null;

            if ($contextGender !== null && ! in_array($contextGender, $genders, true)) {
                return false;
            }
        }

        $categories = (array) ($campaign->target_category_ids ?? []);
        $departments = (array) ($campaign->target_department_ids ?? []);

        if ($categories === [] && $departments === []) {
            return true;
        }

        $contextCategory = $context['category_id'] ?? null;
        $contextDepartment = $context['department_id'] ?? null;

        // On a page with no category context, targeted campaigns simply do not
        // qualify — the general ones will fill the slot.
        if ($contextCategory === null && $contextDepartment === null) {
            return false;
        }

        $categoryMatch = $categories !== [] && in_array($contextCategory, $categories, true);
        $departmentMatch = $departments !== [] && in_array($contextDepartment, $departments, true);

        // A department match alone is enough only when the campaign did not also
        // narrow to specific categories.
        return $categoryMatch || ($departments !== [] && $departmentMatch && $categories === []);
    }

    /* ------------------------------------------------------------------ *
     * Visitor context
     * ------------------------------------------------------------------ */

    public function context(array $overrides = []): array
    {
        $base = $this->visitorContext ??= $this->buildContext();

        return array_merge($base, $overrides);
    }

    private function buildContext(): array
    {
        $user = auth()->user();

        return [
            'is_authenticated' => $user instanceof User,
            'has_paid_order' => $user instanceof User ? $this->hasPaidOrder((int) $user->id) : false,
            'device' => $this->detectDevice((string) request()?->userAgent()),
            'locale' => app()->getLocale(),
            'path' => request()?->path(),
        ];
    }

    /** Cached briefly: this runs once per page, not once per slot. */
    private function hasPaidOrder(int $userId): bool
    {
        return Cache::remember('ad:paid:'.$userId, 300, function () use ($userId) {
            return \App\Models\Order::where('user_id', $userId)->whereNotNull('paid_at')->exists();
        });
    }

    /**
     * Coarse device bucket from the user agent. Deliberately simple: this only
     * decides which of three buckets an ad targets, so a full UA-parsing
     * library would be weight without benefit.
     */
    public function detectDevice(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        if ($ua === '') {
            return AdDevice::Desktop->value;
        }

        // Tablets first — an iPad UA also contains "mobile" on some builds.
        if (str_contains($ua, 'ipad') || (str_contains($ua, 'tablet') && ! str_contains($ua, 'mobile'))) {
            return AdDevice::Tablet->value;
        }

        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return AdDevice::Mobile->value;
        }

        return AdDevice::Desktop->value;
    }

    /* ------------------------------------------------------------------ *
     * Helpers
     * ------------------------------------------------------------------ */

    private function markSeen(AdCampaign $campaign): void
    {
        $this->seenCampaigns[$campaign->id] = ($this->seenCampaigns[$campaign->id] ?? 0) + 1;
    }

    private function medianBid(Collection $campaigns): float
    {
        $bids = $campaigns
            ->map(fn (AdCampaign $campaign) => $campaign->bidPerImpressionMinor())
            ->filter(fn (float $bid) => $bid > 0)
            ->sort()
            ->values();

        if ($bids->isEmpty()) {
            return 5.0;
        }

        $middle = intdiv($bids->count(), 2);

        return $bids->count() % 2 === 0
            ? ($bids[$middle - 1] + $bids[$middle]) / 2
            : $bids[$middle];
    }

    /** Reset the per-request state. Used by tests. */
    public function flush(): void
    {
        $this->seenCampaigns = [];
        $this->resolved = [];
        $this->visitorContext = null;
    }
}
