<div class="space-y-6">
    {{-- ============================================================
         Header
         ============================================================ --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.dashboard') }}</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $rangeLabel }}</p>
        </div>

        <div class="flex items-center gap-1 rounded-lg border border-ink-200 bg-white p-1">
            @foreach ([7 => '7 '.__('hanbell.common.date'), 30 => '30 '.__('hanbell.common.date'), 90 => '90 '.__('hanbell.common.date')] as $value => $label)
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

    {{-- ============================================================
         Operational queues — what needs a decision right now
         ============================================================ --}}
    @if (collect($queues)->sum('count') > 0)
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($queues as $queue)
                @if ($queue['count'] > 0)
                    <a
                        href="{{ $queue['route'] }}"
                        wire:navigate
                        @class([
                            'flex items-center gap-3 rounded-xl border p-4 transition hover:shadow-card',
                            'border-warning-500/30 bg-warning-50' => $queue['tone'] === 'warning',
                            'border-info-500/25 bg-info-50' => $queue['tone'] === 'info',
                            'border-danger-500/25 bg-danger-50' => $queue['tone'] === 'danger',
                        ])
                    >
                        <span @class([
                            'flex size-10 shrink-0 items-center justify-center rounded-lg',
                            'bg-warning-500/15 text-warning-600' => $queue['tone'] === 'warning',
                            'bg-info-500/15 text-info-600' => $queue['tone'] === 'info',
                            'bg-danger-500/15 text-danger-600' => $queue['tone'] === 'danger',
                        ])>
                            <x-dynamic-component :component="'heroicon-o-'.$queue['icon']" class="size-5" />
                        </span>

                        <span class="min-w-0">
                            <span class="block text-xl font-extrabold tabular-nums text-ink-950">{{ $queue['count'] }}</span>
                            <span class="clamp-1 block text-xs font-medium text-ink-600">{{ $queue['label'] }}</span>
                        </span>
                    </a>
                @endif
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         KPI tiles
         ============================================================ --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        @foreach ($kpis as $kpi)
            <div class="rounded-xl border border-ink-200 bg-white p-4" title="{{ $kpi['exact'] }}">
                <div class="flex items-start justify-between gap-2">
                    <span @class([
                        'flex size-9 items-center justify-center rounded-lg',
                        'bg-brand-50 text-brand-600' => $kpi['tone'] === 'brand',
                        'bg-info-50 text-info-600' => $kpi['tone'] === 'info',
                        'bg-accent-100 text-accent-800' => $kpi['tone'] === 'accent',
                        'bg-ink-100 text-ink-500' => $kpi['tone'] === 'neutral',
                    ])>
                        <x-dynamic-component :component="'heroicon-o-'.$kpi['icon']" class="size-4.5" />
                    </span>

                    @if ($kpi['delta'])
                        <span @class([
                            'flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[10px] font-bold',
                            'bg-brand-50 text-brand-700' => $kpi['delta']['direction'] === 'up',
                            'bg-danger-50 text-danger-600' => $kpi['delta']['direction'] === 'down',
                            'bg-ink-100 text-ink-500' => $kpi['delta']['direction'] === 'flat',
                        ])>
                            @if ($kpi['delta']['direction'] === 'up')
                                <x-heroicon-m-arrow-trending-up class="size-3" />
                            @elseif ($kpi['delta']['direction'] === 'down')
                                <x-heroicon-m-arrow-trending-down class="size-3" />
                            @endif
                            {{ abs($kpi['delta']['value']) }}%
                        </span>
                    @endif
                </div>

                <p class="mt-3 text-2xl font-extrabold tabular-nums tracking-tight text-ink-950">{{ $kpi['value'] }}</p>
                <p class="clamp-1 mt-0.5 text-xs font-medium text-ink-500">{{ $kpi['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- ============================================================
         Revenue chart + order status
         ============================================================ --}}
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-ink-200 bg-white p-5 lg:col-span-2">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-bold text-ink-950">{{ __('hanbell.admin.revenue') }}</h2>
                    <p class="mt-0.5 text-xs text-ink-500">{{ __('hanbell.admin.vs_last_period') }}</p>
                </div>

                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1.5 font-medium text-ink-600">
                        <span class="size-2.5 rounded-full bg-brand-600"></span>
                        {{ __('hanbell.admin.revenue') }}
                    </span>
                    <span class="flex items-center gap-1.5 font-medium text-ink-600">
                        <span class="size-2.5 rounded-full bg-accent-300"></span>
                        {{ __('hanbell.admin.orders_count') }}
                    </span>
                </div>
            </div>

            <div class="h-72">
                {{-- Built as a PHP array first: Blade's parser mis-reads a literal
                     array written directly inside @json([...]) in an attribute. --}}
                @php
                    $revenueChartData = [
                        'labels' => $revenueSeries['labels'],
                        'revenue' => $revenueSeries['revenue'],
                        'orders' => $revenueSeries['orders'],
                    ];
                @endphp

                <canvas
                    id="revenueChart"
                    wire:ignore
                    data-chart='@json($revenueChartData)'
                ></canvas>
            </div>
        </div>

        <div class="rounded-xl border border-ink-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold text-ink-950">{{ __('hanbell.order.status') }}</h2>

            @if ($orderStatusBreakdown['values'] === [])
                <x-ui.empty-state icon="chart-pie" :title="__('hanbell.admin.no_data')" class="!py-10" />
            @else
                <div class="h-56">
                    <canvas
                        id="orderStatusChart"
                        wire:ignore
                        data-chart='@json($orderStatusBreakdown)'
                    ></canvas>
                </div>
            @endif
        </div>
    </div>

    {{-- ============================================================
         Product pipeline + top vendors
         ============================================================ --}}
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-ink-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold text-ink-950">{{ __('hanbell.admin.products') }}</h2>

            @if ($productStatusBreakdown['values'] === [])
                <x-ui.empty-state icon="tag" :title="__('hanbell.admin.no_data')" class="!py-10" />
            @else
                <div class="h-56">
                    <canvas
                        id="productStatusChart"
                        wire:ignore
                        data-chart='@json($productStatusBreakdown)'
                    ></canvas>
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-ink-200 bg-white p-5 lg:col-span-2">
            <h2 class="mb-1 text-sm font-bold text-ink-950">{{ __('hanbell.admin.vendors') }}</h2>
            <p class="mb-4 text-xs text-ink-500">{{ __('hanbell.admin.revenue') }}</p>

            @if ($topVendors['labels'] === [])
                <x-ui.empty-state icon="building-storefront" :title="__('hanbell.admin.no_data')" class="!py-10" />
            @else
                <div class="h-64">
                    <canvas
                        id="topVendorsChart"
                        wire:ignore
                        data-chart='@json($topVendors)'
                    ></canvas>
                </div>
            @endif
        </div>
    </div>

    {{-- ============================================================
         Top products + low stock
         ============================================================ --}}
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-ink-200 bg-white lg:col-span-2">
            <div class="border-b border-ink-100 px-5 py-4">
                <h2 class="text-sm font-bold text-ink-950">{{ __('hanbell.product.products') }}</h2>
            </div>

            @if ($topProducts->isEmpty())
                <x-ui.empty-state icon="chart-bar" :title="__('hanbell.admin.no_data')" />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-ink-100 bg-ink-50/60">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-500">
                                <th scope="col" class="px-5 py-2.5">{{ __('hanbell.product.product') }}</th>
                                <th scope="col" class="px-5 py-2.5 text-right">{{ __('hanbell.common.quantity') }}</th>
                                <th scope="col" class="px-5 py-2.5 text-right">{{ __('hanbell.admin.revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @foreach ($topProducts as $row)
                                <tr class="transition hover:bg-ink-50/60">
                                    <td class="max-w-xs px-5 py-3">
                                        <span class="clamp-1 block font-medium text-ink-900">{{ $row->product_name }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-right tabular-nums text-ink-600">{{ $row->units }}</td>
                                    <td class="px-5 py-3 text-right font-semibold tabular-nums text-ink-900">
                                        {{ \App\Support\Money::format((int) $row->revenue) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-ink-200 bg-white">
            <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                <h2 class="text-sm font-bold text-ink-950">{{ __('hanbell.admin.low_stock') }}</h2>

                <a href="{{ route('admin.inventory.index') }}" wire:navigate class="text-xs font-semibold text-brand-700 hover:text-brand-800">
                    {{ __('hanbell.common.view_all') }}
                </a>
            </div>

            @if ($lowStock->isEmpty())
                <x-ui.empty-state icon="archive-box" :title="__('hanbell.common.no_items')" :message="__('hanbell.admin.no_data')" />
            @else
                <ul class="divide-y divide-ink-100">
                    @foreach ($lowStock as $inventory)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="clamp-1 text-sm font-medium text-ink-900">
                                    {{ $inventory->product?->name ?? __('hanbell.common.not_available') }}
                                </p>

                                @if ($inventory->variant)
                                    <p class="text-xs text-ink-500">{{ $inventory->variant->label() }}</p>
                                @endif
                            </div>

                            <span @class([
                                'shrink-0 rounded-full px-2 py-0.5 text-xs font-bold tabular-nums',
                                'bg-danger-50 text-danger-600' => $inventory->available() === 0,
                                'bg-warning-50 text-warning-600' => $inventory->available() > 0,
                            ])>
                                {{ $inventory->available() }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- ============================================================
         Recent orders + advertising
         ============================================================ --}}
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-ink-200 bg-white lg:col-span-2">
            <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                <h2 class="text-sm font-bold text-ink-950">{{ __('hanbell.order.orders') }}</h2>
                <a href="{{ route('admin.orders.index') }}" wire:navigate class="text-xs font-semibold text-brand-700 hover:text-brand-800">
                    {{ __('hanbell.common.view_all') }}
                </a>
            </div>

            @if ($recentOrders->isEmpty())
                <x-ui.empty-state icon="shopping-cart" :title="__('hanbell.admin.no_data')" />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-ink-100 bg-ink-50/60">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-500">
                                <th scope="col" class="px-5 py-2.5">{{ __('hanbell.order.number') }}</th>
                                <th scope="col" class="px-5 py-2.5">{{ __('hanbell.order.status') }}</th>
                                <th scope="col" class="px-5 py-2.5 text-right">{{ __('hanbell.common.total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @foreach ($recentOrders as $order)
                                <tr class="transition hover:bg-ink-50/60">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-semibold text-ink-900 transition hover:text-brand-700">
                                            {{ $order->number }}
                                        </a>
                                        <p class="clamp-1 text-xs text-ink-500">{{ $order->customer_name }}</p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <x-ui.badge :class="$order->status->badgeClasses()" size="sm">
                                            {{ $order->status->label() }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold tabular-nums text-ink-900">
                                        {{ $order->formattedTotal() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Advertising snapshot --}}
        <div class="rounded-xl border border-ink-200 bg-white p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-bold text-ink-950">{{ __('hanbell.admin.advertising') }}</h2>
                <a href="{{ route('admin.ads.reports') }}" wire:navigate class="text-xs font-semibold text-brand-700 hover:text-brand-800">
                    {{ __('hanbell.admin.ad_reports') }}
                </a>
            </div>

            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-ink-500">{{ __('hanbell.admin.campaigns') }}</dt>
                    <dd class="font-bold tabular-nums text-ink-900">{{ $adPerformance['active'] }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-ink-500">Impressions</dt>
                    <dd class="font-bold tabular-nums text-ink-900">{{ number_format($adPerformance['impressions']) }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-ink-500">Clicks</dt>
                    <dd class="font-bold tabular-nums text-ink-900">{{ number_format($adPerformance['clicks']) }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-ink-500">CTR</dt>
                    <dd class="font-bold tabular-nums text-ink-900">{{ $adPerformance['ctr'] }}%</dd>
                </div>
                <div class="flex items-center justify-between border-t border-ink-100 pt-3">
                    <dt class="text-ink-500">Ad spend</dt>
                    <dd class="font-bold tabular-nums text-ink-900">{{ \App\Support\Money::format($adPerformance['spend']) }}</dd>
                </div>
            </dl>

            @if ($adPerformance['impressions'] === 0)
                <p class="mt-4 rounded-lg bg-ink-50 p-3 text-xs text-ink-500">
                    Run <code class="rounded bg-white px-1 py-0.5 font-mono text-[10px]">php artisan ads:aggregate-stats</code>
                    to roll event data into the daily reports.
                </p>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        /**
         * Charts are built from the JSON embedded on each canvas, so no data is
         * duplicated between the server and a separate script block.
         *
         * Chart.js is already on the page via resources/js/app.js (exposed as
         * window.Chart), so there is no CDN dependency and no second copy.
         */
        const money = (value) =>
            '₦' + Number(value).toLocaleString('en-NG', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

        const read = (id) => {
            const canvas = document.getElementById(id);
            if (!canvas) return null;

            try {
                return { canvas, data: JSON.parse(canvas.dataset.chart || '{}') };
            } catch {
                return null;
            }
        };

        const build = () => {
            if (!window.Chart) return;

            const base = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0b0b0a',
                        padding: 10,
                        cornerRadius: 8,
                        titleFont: { family: 'DM Sans', size: 12, weight: '600' },
                        bodyFont: { family: 'DM Sans', size: 12 },
                    },
                },
            };

            /* ---- Revenue + orders ---- */
            const revenue = read('revenueChart');
            if (revenue?.data?.labels) {
                window.hbChart(revenue.canvas, {
                    ...base,
                    data: {
                        labels: revenue.data.labels,
                        datasets: [
                            {
                                type: 'line',
                                label: @json(__('hanbell.admin.revenue')),
                                data: revenue.data.revenue,
                                borderColor: '#178508',
                                backgroundColor: 'rgba(23, 133, 8, 0.12)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.35,
                                pointRadius: 0,
                                pointHoverRadius: 5,
                                pointHoverBackgroundColor: '#178508',
                                yAxisID: 'y',
                            },
                            {
                                type: 'bar',
                                label: @json(__('hanbell.admin.orders_count')),
                                data: revenue.data.orders,
                                backgroundColor: 'rgba(255, 242, 0, 0.85)',
                                borderRadius: 4,
                                barPercentage: 0.6,
                                yAxisID: 'y1',
                            },
                        ],
                    },
                    options: {
                        ...base.options,
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { font: { family: 'DM Sans', size: 10 }, color: '#9d9d96', maxTicksLimit: 10 },
                            },
                            y: {
                                position: 'left',
                                grid: { color: '#efefed' },
                                border: { display: false },
                                ticks: {
                                    font: { family: 'DM Sans', size: 10 },
                                    color: '#9d9d96',
                                    callback: (v) => money(v),
                                },
                            },
                            y1: {
                                position: 'right',
                                grid: { display: false },
                                border: { display: false },
                                ticks: { font: { family: 'DM Sans', size: 10 }, color: '#9d9d96', precision: 0 },
                            },
                        },
                        plugins: {
                            ...base.plugins,
                            tooltip: {
                                ...base.plugins.tooltip,
                                callbacks: {
                                    label: (ctx) =>
                                        ctx.dataset.yAxisID === 'y'
                                            ? ` ${ctx.dataset.label}: ${money(ctx.parsed.y)}`
                                            : ` ${ctx.dataset.label}: ${ctx.parsed.y}`,
                                },
                            },
                        },
                    },
                });
            }

            /* ---- Order status doughnut ---- */
            const status = read('orderStatusChart');
            if (status?.data?.labels) {
                window.hbChart(status.canvas, {
                    type: 'doughnut',
                    data: {
                        labels: status.data.labels,
                        datasets: [{
                            data: status.data.values,
                            backgroundColor: status.data.colors,
                            borderWidth: 0,
                            hoverOffset: 6,
                        }],
                    },
                    options: {
                        ...base.options,
                        cutout: '62%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 8,
                                    boxHeight: 8,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    padding: 12,
                                    font: { family: 'DM Sans', size: 11 },
                                    color: '#565650',
                                },
                            },
                            tooltip: base.plugins.tooltip,
                        },
                    },
                });
            }

            /* ---- Product status doughnut ---- */
            const products = read('productStatusChart');
            if (products?.data?.labels) {
                window.hbChart(products.canvas, {
                    type: 'doughnut',
                    data: {
                        labels: products.data.labels,
                        datasets: [{
                            data: products.data.values,
                            backgroundColor: products.data.colors,
                            borderWidth: 0,
                            hoverOffset: 6,
                        }],
                    },
                    options: {
                        ...base.options,
                        cutout: '62%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 8,
                                    boxHeight: 8,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    padding: 12,
                                    font: { family: 'DM Sans', size: 11 },
                                    color: '#565650',
                                },
                            },
                            tooltip: base.plugins.tooltip,
                        },
                    },
                });
            }

            /* ---- Top vendors horizontal bars ---- */
            const vendors = read('topVendorsChart');
            if (vendors?.data?.labels) {
                window.hbChart(vendors.canvas, {
                    type: 'bar',
                    data: {
                        labels: vendors.data.labels,
                        datasets: [{
                            data: vendors.data.values,
                            backgroundColor: '#178508',
                            borderRadius: 6,
                            barPercentage: 0.7,
                        }],
                    },
                    options: {
                        ...base.options,
                        indexAxis: 'y',
                        plugins: {
                            ...base.plugins,
                            tooltip: {
                                ...base.plugins.tooltip,
                                callbacks: { label: (ctx) => ` ${money(ctx.parsed.x)}` },
                            },
                        },
                        scales: {
                            x: {
                                grid: { color: '#efefed' },
                                border: { display: false },
                                ticks: {
                                    font: { family: 'DM Sans', size: 10 },
                                    color: '#9d9d96',
                                    callback: (v) => money(v),
                                },
                            },
                            y: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: { font: { family: 'DM Sans', size: 11 }, color: '#565650' },
                            },
                        },
                    },
                });
            }
        };

        // Charts must be rebuilt after a Livewire navigation or a re-render
        // replaces the canvases; hbChart destroys any existing instance first.
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', build);
        } else {
            build();
        }

        document.addEventListener('livewire:navigated', build);
        Livewire.hook('morphed', () => build());
    })();
</script>
@endpush
