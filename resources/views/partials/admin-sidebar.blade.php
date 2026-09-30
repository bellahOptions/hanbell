@php
    /**
     * Admin sidebar.
     *
     * Grouped navigation with a live badge on anything awaiting a decision —
     * the operator's job is to clear queues, so the queues are what the
     * navigation surfaces.
     */
    $pendingProducts = \App\Models\Product::where('status', \App\Enums\ProductStatus::PendingReview)->count();
    $pendingVendors = \App\Models\Vendor::where('status', \App\Enums\VendorStatus::Pending)->count();
    $pendingReviews = \App\Models\Review::pending()->count();
    $unpaidOrders = \App\Models\Order::unpaid()->count();

    $groups = [
        [
            'label' => null,
            'items' => [
                ['route' => 'admin.dashboard', 'icon' => 'squares-2x2', 'label' => __('hanbell.admin.dashboard'), 'match' => 'admin.dashboard'],
            ],
        ],
        [
            'label' => __('hanbell.admin.sales'),
            'items' => [
                ['route' => 'admin.orders.index', 'icon' => 'shopping-cart', 'label' => __('hanbell.admin.orders'), 'match' => 'admin.orders.*', 'badge' => $unpaidOrders, 'badgeTone' => 'warning'],
                ['route' => 'admin.payments.index', 'icon' => 'credit-card', 'label' => __('hanbell.admin.payments'), 'match' => 'admin.payments.*'],
                ['route' => 'admin.customers.index', 'icon' => 'users', 'label' => __('hanbell.admin.customers'), 'match' => 'admin.customers.*'],
            ],
        ],
        [
            'label' => __('hanbell.admin.catalogue'),
            'items' => [
                ['route' => 'admin.products.index', 'icon' => 'tag', 'label' => __('hanbell.admin.products'), 'match' => 'admin.products.*', 'badge' => $pendingProducts, 'badgeTone' => 'brand'],
                ['route' => 'admin.inventory.index', 'icon' => 'archive-box', 'label' => __('hanbell.admin.inventory'), 'match' => 'admin.inventory.*'],
                ['route' => 'admin.categories.index', 'icon' => 'folder', 'label' => __('hanbell.admin.categories'), 'match' => 'admin.categories.*'],
                ['route' => 'admin.departments.index', 'icon' => 'rectangle-stack', 'label' => __('hanbell.admin.departments'), 'match' => 'admin.departments.*'],
                ['route' => 'admin.brands.index', 'icon' => 'bookmark', 'label' => __('hanbell.admin.brands'), 'match' => 'admin.brands.*'],
                ['route' => 'admin.tags.index', 'icon' => 'hashtag', 'label' => __('hanbell.admin.tags'), 'match' => 'admin.tags.*'],
                ['route' => 'admin.attributes.index', 'icon' => 'adjustments-horizontal', 'label' => __('hanbell.admin.attributes'), 'match' => 'admin.attributes.*'],
            ],
        ],
        [
            'label' => __('hanbell.admin.vendors'),
            'items' => [
                ['route' => 'admin.vendors.index', 'icon' => 'building-storefront', 'label' => __('hanbell.admin.vendors'), 'match' => 'admin.vendors.*', 'badge' => $pendingVendors, 'badgeTone' => 'warning'],
            ],
        ],
        [
            'label' => __('hanbell.admin.advertising'),
            'items' => [
                ['route' => 'admin.ads.index', 'icon' => 'megaphone', 'label' => __('hanbell.admin.campaigns'), 'match' => 'admin.ads.index'],
                ['route' => 'admin.ads.reports', 'icon' => 'chart-bar', 'label' => __('hanbell.admin.ad_reports'), 'match' => 'admin.ads.reports'],
                ['route' => 'admin.ads.placements', 'icon' => 'view-columns', 'label' => __('hanbell.admin.placements'), 'match' => 'admin.ads.placements'],
                ['route' => 'admin.ads.advertisers', 'icon' => 'briefcase', 'label' => __('hanbell.admin.advertisers'), 'match' => 'admin.ads.advertisers'],
            ],
        ],
        [
            'label' => __('hanbell.admin.content'),
            'items' => [
                ['route' => 'admin.pages.index', 'icon' => 'document-text', 'label' => __('hanbell.admin.pages'), 'match' => 'admin.pages.*'],
                ['route' => 'admin.reviews.index', 'icon' => 'star', 'label' => __('hanbell.admin.reviews'), 'match' => 'admin.reviews.*', 'badge' => $pendingReviews, 'badgeTone' => 'warning'],
                ['route' => 'admin.newsletter.index', 'icon' => 'envelope', 'label' => __('hanbell.admin.newsletter'), 'match' => 'admin.newsletter.*'],
            ],
        ],
        [
            'label' => __('hanbell.admin.seo'),
            'items' => [
                ['route' => 'admin.seo.index', 'icon' => 'globe-alt', 'label' => __('hanbell.admin.seo_settings'), 'match' => 'admin.seo.index'],
                ['route' => 'admin.seo.redirects', 'icon' => 'arrow-uturn-right', 'label' => __('hanbell.admin.redirects'), 'match' => 'admin.seo.redirects'],
            ],
        ],
        [
            'label' => __('hanbell.admin.system'),
            'items' => [
                ['route' => 'admin.settings.index', 'icon' => 'cog-6-tooth', 'label' => __('hanbell.admin.settings'), 'match' => 'admin.settings.*'],
                ['route' => 'admin.audit.index', 'icon' => 'clipboard-document-list', 'label' => __('hanbell.admin.audit_log'), 'match' => 'admin.audit.*'],
            ],
        ],
    ];
@endphp

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-ink-200 bg-white transition-transform duration-200 lg:translate-x-0"
    aria-label="{{ __('hanbell.admin.admin_panel') }}"
>
    {{-- Brand --}}
    <div class="flex h-16 shrink-0 items-center gap-2.5 border-b border-ink-200 px-5">
        <x-site.mark size="size-8" />
        <div class="min-w-0">
            <p class="clamp-1 text-sm font-bold text-ink-950">{{ config('hanbell.name') }}</p>
            <p class="text-[10px] font-semibold uppercase tracking-wider text-ink-400">
                {{ __('hanbell.admin.admin_panel') }}
            </p>
        </div>

        <button
            type="button"
            @click="sidebarOpen = false"
            class="ml-auto rounded-md p-1.5 text-ink-400 transition hover:bg-ink-100 lg:hidden"
            aria-label="{{ __('hanbell.nav.close') }}"
        >
            <x-heroicon-o-x-mark class="size-4" />
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="{{ __('hanbell.admin.admin_panel') }}">
        @foreach ($groups as $group)
            @if ($group['label'])
                <p class="mb-1.5 mt-4 px-3 text-[10px] font-bold uppercase tracking-wider text-ink-400 first:mt-0">
                    {{ $group['label'] }}
                </p>
            @endif

            <ul class="space-y-0.5">
                @foreach ($group['items'] as $item)
                    @php $active = request()->routeIs($item['match']); @endphp

                    <li>
                        <a
                            href="{{ route($item['route']) }}"
                            wire:navigate
                            class="hb-nav-item"
                            @if ($active) data-active="true" aria-current="page" @endif
                        >
                            <x-dynamic-component :component="'heroicon-o-'.$item['icon']" class="size-4.5 shrink-0" />

                            <span class="flex-1 truncate">{{ $item['label'] }}</span>

                            @if (! empty($item['badge']))
                                <span @class([
                                    'rounded-full px-1.5 py-0.5 text-[10px] font-bold',
                                    'bg-white/25 text-white' => $active,
                                    'bg-brand-100 text-brand-700' => ! $active && ($item['badgeTone'] ?? '') === 'brand',
                                    'bg-warning-100 text-warning-700' => ! $active && ($item['badgeTone'] ?? '') === 'warning',
                                ])>
                                    {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>

    {{-- Footer --}}
    <div class="shrink-0 border-t border-ink-200 p-3">
        <a
            href="{{ route('storefront.home') }}"
            class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-medium text-ink-600 transition hover:bg-ink-100 hover:text-ink-900"
        >
            <x-heroicon-o-arrow-top-right-on-square class="size-4.5" />
            {{ __('hanbell.admin.back_to_store') }}
        </a>
    </div>
</aside>
