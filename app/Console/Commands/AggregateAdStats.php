<?php

namespace App\Console\Commands;

use App\Enums\AdEventType;
use App\Models\AdCampaign;
use App\Models\AdCreative;
use App\Models\AdDailyStat;
use App\Models\AdEvent;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Roll ad_events into ad_daily_stats.
 *
 * The serving algorithm and every admin chart read the rollup, so this keeps the
 * hot path off the unbounded event table.
 *
 * It is written to be safely re-runnable (scheduled twice daily): each run
 * recomputes the window from scratch and upserts, so a late-arriving event is
 * folded in rather than double-counted.
 */
class AggregateAdStats extends Command
{
    protected $signature = 'ads:aggregate-stats
                            {--days=30 : How many days back to recompute}
                            {--date= : Recompute a single day (Y-m-d)}';

    protected $description = 'Aggregate ad events into daily statistics';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $singleDate = $this->option('date');

        $dates = $singleDate
            ? [CarbonImmutable::parse($singleDate)->toDateString()]
            : $this->dateRange($days);

        $this->info(sprintf(
            'Aggregating ad statistics for %d day(s)…',
            count($dates),
        ));

        $rows = 0;
        $uniqueRows = 0;
        $combined = 0;

        foreach ($dates as $date) {
            $rows += $this->aggregatePerCreative($date);
            $uniqueRows += $this->aggregateUniqueVisitors($date);
            $combined += $this->aggregateCampaignTotals($date);
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Rows written'],
            [
                ['Per-creative rows', $rows],
                ['Unique-visitor updates', $uniqueRows],
                ['Campaign-level rows', $combined],
            ],
        );

        $this->info('Done.');

        return self::SUCCESS;
    }

    /**
     * One row per (campaign, creative, day).
     */
    private function aggregatePerCreative(string $date): int
    {
        $start = CarbonImmutable::parse($date)->startOfDay();
        $end = $start->endOfDay();

        $totals = AdEvent::query()
            ->whereBetween('occurred_at', [$start, $end])
            ->groupBy('ad_campaign_id', 'ad_creative_id')
            ->select([
                'ad_campaign_id',
                'ad_creative_id',
                DB::raw("SUM(CASE WHEN event_type = '".AdEventType::Impression->value."' THEN 1 ELSE 0 END) as impressions"),
                DB::raw("SUM(CASE WHEN event_type = '".AdEventType::Click->value."' THEN 1 ELSE 0 END) as clicks"),
                DB::raw("SUM(CASE WHEN event_type = '".AdEventType::Conversion->value."' THEN 1 ELSE 0 END) as conversions"),
                DB::raw('SUM(spend_minor) as spend_minor'),
            ])
            ->get();

        $written = 0;

        // Zero out any (campaign, creative, day) rows that no longer have
        // events — but only for the day being recomputed, so a live day's
        // figures are never wiped by another date's pass.
        $seenKeys = $totals
            ->map(fn ($row) => $row->ad_campaign_id.':'.$row->ad_creative_id)
            ->all();

        AdDailyStat::query()
            ->where('date', $date)
            ->whereNotNull('ad_creative_id')
            ->get()
            ->each(function (AdDailyStat $stat) use ($seenKeys) {
                $key = $stat->ad_campaign_id.':'.$stat->ad_creative_id;

                if (! in_array($key, $seenKeys, true)) {
                    $stat->forceFill([
                        'impressions' => 0,
                        'clicks' => 0,
                        'conversions' => 0,
                        'spend_minor' => 0,
                    ])->save();
                }
            });

        foreach ($totals as $row) {
            $this->upsert($date, (int) $row->ad_campaign_id, (int) $row->ad_creative_id, [
                'impressions' => (int) $row->impressions,
                'clicks' => (int) $row->clicks,
                'conversions' => (int) $row->conversions,
                'spend_minor' => (int) $row->spend_minor,
            ]);

            $written++;
        }

        return $written;
    }

    /**
     * Distinct visitors per campaign per day.
     *
     * The visitor hash already rotates daily, so counting distinct hashes inside
     * one day gives a daily-unique figure without any cross-day linkage.
     */
    private function aggregateUniqueVisitors(string $date): int
    {
        $start = CarbonImmutable::parse($date)->startOfDay();
        $end = $start->endOfDay();

        $rows = AdEvent::query()
            ->whereBetween('occurred_at', [$start, $end])
            ->whereNotNull('visitor_hash')
            ->groupBy('ad_campaign_id')
            ->select([
                'ad_campaign_id',
                DB::raw('COUNT(DISTINCT visitor_hash) as unique_visitors'),
            ])
            ->get();

        foreach ($rows as $row) {
            AdDailyStat::query()
                ->where('date', $date)
                ->where('ad_campaign_id', $row->ad_campaign_id)
                ->update(['unique_visitors' => (int) $row->unique_visitors]);
        }

        return $rows->count();
    }

    /**
     * Campaign-level rows (creative_id null) summing the day across creatives —
     * what the dashboard's per-campaign table reads.
     */
    private function aggregateCampaignTotals(string $date): int
    {
        $start = CarbonImmutable::parse($date)->startOfDay();
        $end = $start->endOfDay();

        $rows = AdEvent::query()
            ->whereBetween('occurred_at', [$start, $end])
            ->groupBy('ad_campaign_id')
            ->select([
                'ad_campaign_id',
                DB::raw("SUM(CASE WHEN event_type = '".AdEventType::Impression->value."' THEN 1 ELSE 0 END) as impressions"),
                DB::raw("SUM(CASE WHEN event_type = '".AdEventType::Click->value."' THEN 1 ELSE 0 END) as clicks"),
                DB::raw("SUM(CASE WHEN event_type = '".AdEventType::Conversion->value."' THEN 1 ELSE 0 END) as conversions"),
                DB::raw('SUM(spend_minor) as spend_minor'),
                DB::raw('COUNT(DISTINCT visitor_hash) as unique_visitors'),
            ])
            ->get();

        $written = 0;

        foreach ($rows as $row) {
            $this->upsert($date, (int) $row->ad_campaign_id, null, [
                'impressions' => (int) $row->impressions,
                'clicks' => (int) $row->clicks,
                'conversions' => (int) $row->conversions,
                'spend_minor' => (int) $row->spend_minor,
                'unique_visitors' => (int) $row->unique_visitors,
            ]);

            $written++;
        }

        return $written;
    }

    /**
     * @param  array<string,int>  $attributes
     */
    private function upsert(string $date, int $campaignId, ?int $creativeId, array $attributes): void
    {
        AdDailyStat::updateOrCreate(
            [
                'ad_campaign_id' => $campaignId,
                'ad_creative_id' => $creativeId,
                'date' => $date,
            ],
            $attributes,
        );
    }

    /** @return list<string> */
    private function dateRange(int $days): array
    {
        $dates = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $dates[] = now()->subDays($i)->toDateString();
        }

        return $dates;
    }
}
