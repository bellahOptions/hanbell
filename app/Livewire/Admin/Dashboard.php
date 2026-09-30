<?php

namespace App\Livewire\Admin;

use App\Enums\AdCampaignStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\VendorStatus;
use App\Models\AdCampaign;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Money;
use App\Support\Seo;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Admin dashboard.
 *
 * Every figure is a live aggregate — nothing here is a fixture. Two rules were
 * followed deliberately:
 *
 *  1. Status colours come from an explicit status→hex map on the enum, never a
 *     positional array. ProductStatus has seven cases; a positional list that is
 *     one short silently mis-colours a bar, which is exactly the bug that is
 *     hardest to notice on a dashboard.
 *
 *  2. Charts read pre-aggregated data (a single GROUP BY per series), not one
 *     query per point.
 */
#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public int $days = 30;

    public function updatedDays(): void
    {
        $this->days = in_array($this->days, [7, 30, 90], true) ? $this->days : 30;
    }

    public function render(): View
    {
        $from = CarbonImmutable::now()->subDays($this->days - 1)->startOfDay();
        $to = CarbonImmutable::now()->endOfDay();

        // The immediately preceding window of equal length, for the deltas.
        $previousFrom = $from->copy()->subDays($this->days);
        $previousTo = $from->copy()->subSecond();

        return view('livewire.admin.dashboard', [
            'rangeLabel' => $from->format('j M').' – '.$to->format('j M Y'),
            'kpis' => $this->kpis($from, $to, $previousFrom, $previousTo),
            'revenueSeries' => $this->revenueSeries($from, $to),
            'orderStatusBreakdown' => $this->orderStatusBreakdown(),
            'productStatusBreakdown' => $this->productStatusBreakdown(),
            'topVendors' => $this->topVendors($from, $to),
            'topProducts' => $this->topProducts($from, $to),
            'lowStock' => $this->lowStock(),
            'recentOrders' => $this->recentOrders(),
            'queues' => $this->queues(),
            'adPerformance' => $this->adPerformance($from, $to),
            'seo' => $this->seo(),
        ]);
    }

    /* ------------------------------------------------------------------ *
     * KPIs
     * ------------------------------------------------------------------ */

    /**
     * @return array<int,array<string,mixed>>
     */
    private function kpis($from, $to, $previousFrom, $previousTo): array
    {
        $revenue = (int) Order::paid()->whereBetween('created_at', [$from, $to])->sum('total_minor');
        $previousRevenue = (int) Order::paid()->whereBetween('created_at', [$previousFrom, $previousTo])->sum('total_minor');

        $orders = Order::paid()->whereBetween('created_at', [$from, $to])->count();
        $previousOrders = Order::paid()->whereBetween('created_at', [$previousFrom, $previousTo])->count();

        $commission = (int) Order::paid()->whereBetween('created_at', [$from, $to])->sum('commission_minor');
        $previousCommission = (int) Order::paid()->whereBetween('created_at', [$previousFrom, $previousTo])->sum('commission_minor');

        $newCustomers = User::whereBetween('created_at', [$from, $to])->count();
        $previousCustomers = User::whereBetween('created_at', [$previousFrom, $previousTo])->count();

        $averageOrder = $orders > 0 ? (int) round($revenue / $orders) : 0;
        $previousAverage = $previousOrders > 0 ? (int) round($previousRevenue / $previousOrders) : 0;

        return [
            [
                'label' => __('hanbell.admin.revenue'),
                'value' => Money::compact($revenue),
                'exact' => Money::format($revenue),
                'delta' => $this->delta($revenue, $previousRevenue),
                'icon' => 'banknotes',
                'tone' => 'brand',
            ],
            [
                'label' => __('hanbell.admin.orders_count'),
                'value' => number_format($orders),
                'exact' => trans_choice('hanbell.shop.results_count', $orders, ['count' => number_format($orders)]),
                'delta' => $this->delta($orders, $previousOrders),
                'icon' => 'shopping-cart',
                'tone' => 'info',
            ],
            [
                'label' => __('hanbell.admin.average_order'),
                'value' => Money::compact($averageOrder),
                'exact' => Money::format($averageOrder),
                'delta' => $this->delta($averageOrder, $previousAverage),
                'icon' => 'chart-bar',
                'tone' => 'accent',
            ],
            [
                'label' => __('hanbell.admin.commission_earned'),
                'value' => Money::compact($commission),
                'exact' => Money::format($commission),
                'delta' => $this->delta($commission, $previousCommission),
                'icon' => 'receipt-percent',
                'tone' => 'brand',
            ],
            [
                'label' => __('hanbell.admin.new_customers'),
                'value' => number_format($newCustomers),
                'exact' => trans_choice('hanbell.vendor.products_count', $newCustomers, ['count' => number_format($newCustomers)]),
                'delta' => $this->delta($newCustomers, $previousCustomers),
                'icon' => 'user-plus',
                'tone' => 'info',
            ],
            [
                'label' => __('hanbell.admin.vendor_payouts'),
                'value' => Money::compact(max(0, $revenue - $commission)),
                'exact' => Money::format(max(0, $revenue - $commission)),
                'delta' => null,
                'icon' => 'building-storefront',
                'tone' => 'neutral',
            ],
        ];
    }

    /**
     * Percentage change against the previous window.
     * Returns null when there is no baseline — showing "+100%" against zero is
     * noise, not information.
     */
    private function delta(int $current, int $previous): ?array
    {
        if ($previous === 0) {
            return null;
        }

        $change = (($current - $previous) / $previous) * 100;

        return [
            'value' => round($change, 1),
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat'),
        ];
    }

    /* ------------------------------------------------------------------ *
     * Charts
     * ------------------------------------------------------------------ */

    /**
     * One grouped query for the whole series rather than a query per day.
     *
     * @return array{labels:array<int,string>,revenue:array<int,float>,orders:array<int,int>}
     */
    private function revenueSeries($from, $to): array
    {
        // One grouped query per series, then bucketed by day into a complete
        // calendar — including quiet days, so the line does not skip them and
        // imply uninterrupted growth.
        $revenueByDay = Order::paid()
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('day')
            ->select([
                DB::raw('DATE(created_at) as day'),
                DB::raw('SUM(total_minor) as revenue'),
            ])
            ->pluck('revenue', 'day');

        $ordersByDay = Order::paid()
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('day')
            ->select([
                DB::raw('DATE(created_at) as day'),
                DB::raw('COUNT(*) as orders'),
            ])
            ->pluck('orders', 'day');

        $labels = [];
        $revenue = [];
        $orders = [];

        /*
         * CarbonImmutable::addDay() returns a NEW instance — it does not mutate
         * — so writing this as `for (...; $date->addDay())` never advances the
         * loop variable and spins forever, exhausting memory. The reassignment
         * is not optional here.
         */
        for ($date = $from->copy(); $date->lte($to); $date = $date->addDay()) {
            $key = $date->toDateString();

            $labels[] = $date->format('j M');
            $revenue[] = round(((int) ($revenueByDay[$key] ?? 0)) / 100, 2);
            $orders[] = (int) ($ordersByDay[$key] ?? 0);
        }

        return ['labels' => $labels, 'revenue' => $revenue, 'orders' => $orders];
    }

    /**
     * @return array{labels:array<int,string>,values:array<int,int>,colors:array<int,string>}
     */
    private function orderStatusBreakdown(): array
    {
        $counts = Order::query()
            ->groupBy('status')
            ->select(['status', DB::raw('COUNT(*) as aggregate')])
            ->pluck('aggregate', 'status');

        $labels = [];
        $values = [];
        $colors = [];

        foreach (OrderStatus::cases() as $status) {
            $count = (int) ($counts[$status->value] ?? 0);

            if ($count === 0) {
                continue;
            }

            $labels[] = $status->label();
            $values[] = $count;
            // Colour comes from the enum itself, so it can never drift out of
            // step with the status list.
            $colors[] = $status->hex();
        }

        return ['labels' => $labels, 'values' => $values, 'colors' => $colors];
    }

    /**
     * @return array{labels:array<int,string>,values:array<int,int>,colors:array<int,string>}
     */
    private function productStatusBreakdown(): array
    {
        $counts = Product::query()
            ->groupBy('status')
            ->select(['status', DB::raw('COUNT(*) as aggregate')])
            ->pluck('aggregate', 'status');

        $labels = [];
        $values = [];
        $colors = [];

        // Explicit map, keyed by the enum value — a positional array here is the
        // classic way to mis-colour a seven-case enum.
        $palette = [
            ProductStatus::Draft->value => '#9d9d96',
            ProductStatus::PendingReview->value => '#f59e0b',
            ProductStatus::Approved->value => '#3b82f6',
            ProductStatus::Rejected->value => '#ef4444',
            ProductStatus::Published->value => '#178508',
            ProductStatus::Unpublished->value => '#565650',
            ProductStatus::Archived->value => '#171715',
        ];

        foreach (ProductStatus::cases() as $status) {
            $count = (int) ($counts[$status->value] ?? 0);

            if ($count === 0) {
                continue;
            }

            $labels[] = $status->label();
            $values[] = $count;
            $colors[] = $palette[$status->value] ?? '#9d9d96';
        }

        return ['labels' => $labels, 'values' => $values, 'colors' => $colors];
    }

    /**
     * @return array{labels:array<int,string>,values:array<int,float>}
     */
    private function topVendors($from, $to): array
    {
        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotNull('orders.paid_at')
            ->whereBetween('orders.created_at', [$from, $to])
            ->groupBy('order_items.vendor_id', 'order_items.vendor_name')
            ->select([
                'order_items.vendor_name',
                DB::raw('SUM(order_items.line_total_minor) as revenue'),
            ])
            ->orderByDesc('revenue')
            ->limit(6)
            ->get();

        return [
            'labels' => $rows->pluck('vendor_name')->all(),
            'values' => $rows->map(fn ($row) => round(((int) $row->revenue) / 100, 2))->all(),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int,object>
     */
    private function topProducts($from, $to)
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotNull('orders.paid_at')
            ->whereBetween('orders.created_at', [$from, $to])
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->select([
                'order_items.product_id',
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as units'),
                DB::raw('SUM(order_items.line_total_minor) as revenue'),
            ])
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();
    }

    /* ------------------------------------------------------------------ *
     * Operational queues
     * ------------------------------------------------------------------ */

    private function lowStock()
    {
        return Inventory::query()
            ->lowStock()
            // The product and variant names are read for every row; without
            // eager loading this is two extra queries per line, which on a
            // seeded catalogue is the difference between a fast dashboard and
            // an out-of-memory failure.
            ->with([
                'product' => fn ($q) => $q->select('id', 'name', 'slug', 'uuid', 'token_version'),
                'variant' => fn ($q) => $q->select('id', 'name', 'product_id'),
            ])
            ->orderByRaw('quantity_on_hand - quantity_reserved ASC')
            ->limit(6)
            ->get();
    }

    private function recentOrders()
    {
        return Order::query()
            ->withCount('items')
            ->latest()
            ->limit(8)
            ->get();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function queues(): array
    {
        return [
            [
                'label' => __('hanbell.admin.pending_review'),
                'count' => Product::where('status', ProductStatus::PendingReview)->count(),
                'route' => route('admin.products.index'),
                'icon' => 'tag',
                'tone' => 'warning',
            ],
            [
                'label' => __('hanbell.vendor.status_pending'),
                'count' => Vendor::where('status', VendorStatus::Pending)->count(),
                'route' => route('admin.vendors.index'),
                'icon' => 'building-storefront',
                'tone' => 'warning',
            ],
            [
                'label' => __('hanbell.admin.reviews'),
                'count' => Review::pending()->count(),
                'route' => route('admin.reviews.index'),
                'icon' => 'star',
                'tone' => 'info',
            ],
            [
                'label' => __('hanbell.order.statuses.pending'),
                'count' => Order::unpaid()->count(),
                'route' => route('admin.orders.index'),
                'icon' => 'clock',
                'tone' => 'danger',
            ],
        ];
    }

    /**
     * @return array{impressions:int,clicks:int,ctr:float,spend:int,active:int}
     */
    private function adPerformance($from, $to): array
    {
        $impressions = (int) DB::table('ad_events')
            ->where('event_type', 'impression')
            ->whereBetween('occurred_at', [$from, $to])
            ->count();

        $clicks = (int) DB::table('ad_events')
            ->where('event_type', 'click')
            ->whereBetween('occurred_at', [$from, $to])
            ->count();

        $spend = (int) DB::table('ad_events')
            ->whereBetween('occurred_at', [$from, $to])
            ->sum('spend_minor');

        return [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0.0,
            'spend' => $spend,
            'active' => AdCampaign::where('status', AdCampaignStatus::Active)->count(),
        ];
    }

    private function seo(): Seo
    {
        return app(Seo::class)
            ->title(__('hanbell.admin.dashboard'))
            ->noindex();
    }
}
