<?php

namespace App\Livewire\Admin\Ads;

use App\Models\AdCampaign;
use App\Models\AdDailyStat;
use App\Support\Money;
use App\Support\Seo;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Advertising performance.
 *
 * Reads the ad_daily_stats rollup (written by `ads:aggregate-stats`), never the
 * raw ad_events table — the events table is unbounded and a report that scans it
 * gets slower every day the store is open.
 */
#[Layout('layouts.admin')]
class Reports extends Component
{
    public int $days = 30;

    public function render(): View
    {
        $from = CarbonImmutable::now()->subDays($this->days - 1)->startOfDay();
        $to = CarbonImmutable::now()->endOfDay();

        // Campaign-level rows only; the per-creative rows are rolled into them.
        $rows = AdDailyStat::query()
            ->whereNull('ad_creative_id')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('ad_campaign_id')
            ->select([
                'ad_campaign_id',
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(conversions) as conversions'),
                DB::raw('SUM(spend_minor) as spend_minor'),
                DB::raw('SUM(unique_visitors) as unique_visitors'),
            ])
            ->get();

        $campaigns = AdCampaign::withTrashed()
            ->whereIn('id', $rows->pluck('ad_campaign_id'))
            ->get()
            ->keyBy('id');

        $totals = [
            'impressions' => (int) $rows->sum('impressions'),
            'clicks' => (int) $rows->sum('clicks'),
            'conversions' => (int) $rows->sum('conversions'),
            'spend_minor' => (int) $rows->sum('spend_minor'),
            'unique_visitors' => (int) $rows->sum('unique_visitors'),
        ];

        // Daily series for the trend chart.
        $series = AdDailyStat::query()
            ->whereNull('ad_creative_id')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('date')
            ->select([
                'date',
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(spend_minor) as spend_minor'),
            ])
            ->orderBy('date')
            ->get();

        return view('livewire.admin.ads.reports', [
            'rows' => $rows->sortByDesc('impressions'),
            'campaigns' => $campaigns,
            'totals' => $totals,
            'series' => [
                'labels' => $series->pluck('date')->all(),
                'impressions' => $series->pluck('impressions')->map(fn ($v) => (int) $v)->all(),
                'clicks' => $series->pluck('clicks')->map(fn ($v) => (int) $v)->all(),
                'spend' => $series->pluck('spend_minor')->map(fn ($v) => round(((int) $v) / 100, 2))->all(),
            ],
            'seo' => app(Seo::class)->title(__('hanbell.admin.ad_reports'))->noindex(),
        ]);
    }
}