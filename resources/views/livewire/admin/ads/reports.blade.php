<div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.ad_reports') }}</h1>
            <p class="mt-1 text-sm text-ink-500">
                Read from the daily rollup. Rebuild it with
                <code class="rounded bg-ink-100 px-1.5 py-0.5 font-mono text-[11px]">php artisan ads:aggregate-stats</code>.
            </p>
        </div>

        <div class="flex items-center gap-1 rounded-lg border border-ink-200 bg-white p-1">
            @foreach ([7, 30, 90] as $value)
                <button
                    type="button"
                    wire:click="$set('days', {{ $value }})"
                    @class([
                        'rounded-md px-3 py-1.5 text-xs font-semibold transition',
                        'bg-ink-950 text-white' => $days === $value,
                        'text-ink-600 hover:bg-ink-100' => $days !== $value,
                    ])
                >
                    {{ $value }}d
                </button>
            @endforeach
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <x-admin.stat label="Impressions" :value="number_format($totals['impressions'])" icon="eye" tone="info" />
        <x-admin.stat label="Clicks" :value="number_format($totals['clicks'])" icon="cursor-arrow-rays" tone="brand" />
        <x-admin.stat label="Unique visitors" :value="number_format($totals['unique_visitors'])" icon="users" tone="neutral" />
        <x-admin.stat label="CTR" :value="($totals['impressions'] > 0 ? round($totals['clicks'] / $totals['impressions'] * 100, 2) : 0).'%'" icon="chart-bar" tone="accent" />
        <x-admin.stat label="Ad spend" :value="\App\Support\Money::compact($totals['spend_minor'])" icon="banknotes" tone="brand" />
    </div>

    {{-- Daily trend --}}
    <x-admin.panel title="Delivery over time">
        @if (empty($series['labels']))
            <x-ui.empty-state icon="chart-bar" :title="__('hanbell.admin.no_data')" :message="__('hanbell.common.no_items')" />
        @else
            <div class="h-72">
                <canvas id="adTrendChart" wire:ignore data-chart='@json($series)'></canvas>
            </div>
        @endif
    </x-admin.panel>

    {{-- Per-campaign --}}
    <x-admin.panel :title="__('hanbell.admin.campaigns')" padding="none">
        @if ($rows->isEmpty())
            <x-ui.empty-state
                icon="megaphone"
                :title="__('hanbell.admin.no_data')"
                message="No ad delivery has been recorded in this window yet."
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-ink-200 bg-ink-50/60">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-500">
                            <th scope="col" class="px-4 py-2.5">Campaign</th>
                            <th scope="col" class="px-4 py-2.5 text-right">Impressions</th>
                            <th scope="col" class="px-4 py-2.5 text-right">Clicks</th>
                            <th scope="col" class="px-4 py-2.5 text-right">CTR</th>
                            <th scope="col" class="px-4 py-2.5 text-right">Conversions</th>
                            <th scope="col" class="px-4 py-2.5 text-right">Spend</th>
                            <th scope="col" class="px-4 py-2.5 text-right">eCPM</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100">
                        @foreach ($rows as $row)
                            @php $campaign = $campaigns[$row->ad_campaign_id] ?? null; @endphp

                            <tr class="transition hover:bg-ink-50/60">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-ink-900">{{ $campaign?->name ?? 'Deleted campaign' }}</p>
                                    <p class="text-xs text-ink-500">
                                        {{ $campaign?->advertiser?->name ?? $campaign?->vendor?->name ?? 'House' }}
                                    </p>
                                </td>

                                <td class="px-4 py-3 text-right tabular-nums text-ink-700">{{ number_format($row->impressions) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-ink-700">{{ number_format($row->clicks) }}</td>

                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-ink-900">
                                    {{ $row->impressions > 0 ? round($row->clicks / $row->impressions * 100, 2) : 0 }}%
                                </td>

                                <td class="px-4 py-3 text-right tabular-nums text-ink-700">{{ number_format($row->conversions) }}</td>

                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-ink-900">
                                    {{ \App\Support\Money::format((int) $row->spend_minor) }}
                                </td>

                                <td class="px-4 py-3 text-right tabular-nums text-ink-600">
                                    {{ $row->impressions > 0 ? \App\Support\Money::format((int) round($row->spend_minor / $row->impressions * 1000)) : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.panel>
</div>

@push('scripts')
<script>
    (() => {
        const canvas = document.getElementById('adTrendChart');
        if (!canvas || !window.Chart) return;

        let data;
        try { data = JSON.parse(canvas.dataset.chart || '{}'); } catch { return; }
        if (!data.labels) return;

        window.hbChart(canvas, {
            data: {
                labels: data.labels,
                datasets: [
                    {
                        type: 'line',
                        label: 'Impressions',
                        data: data.impressions,
                        borderColor: '#178508',
                        backgroundColor: 'rgba(23,133,8,0.12)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 0,
                        yAxisID: 'y',
                    },
                    {
                        type: 'bar',
                        label: 'Clicks',
                        data: data.clicks,
                        backgroundColor: 'rgba(255,242,0,0.85)',
                        borderRadius: 4,
                        barPercentage: 0.6,
                        yAxisID: 'y1',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#0b0b0a', padding: 10, cornerRadius: 8 },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { family: 'DM Sans', size: 10 }, color: '#9d9d96', maxTicksLimit: 10 } },
                    y: { position: 'left', grid: { color: '#efefed' }, border: { display: false }, ticks: { font: { family: 'DM Sans', size: 10 }, color: '#9d9d96' } },
                    y1: { position: 'right', grid: { display: false }, border: { display: false }, ticks: { font: { family: 'DM Sans', size: 10 }, color: '#9d9d96', precision: 0 } },
                },
            },
        });
    })();
</script>
@endpush
