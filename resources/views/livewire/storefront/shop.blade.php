@php
    use App\Enums\AdPlacementKey;
@endphp

<div>
    {{-- Breadcrumb --}}
    <nav class="border-b border-ink-100 bg-ink-50/60" aria-label="Breadcrumb">
        <div class="hb-container">
            <ol class="flex items-center gap-1.5 py-3 text-xs text-ink-500">
                <li>
                    <a href="{{ route('storefront.home') }}" wire:navigate class="transition hover:text-brand-700">
                        {{ __('hanbell.nav.home') }}
                    </a>
                </li>
                <li aria-hidden="true"><x-heroicon-m-chevron-right class="size-3" /></li>
                <li>
                    <a href="{{ route('storefront.shop') }}" wire:navigate class="transition hover:text-brand-700">
                        {{ __('hanbell.nav.shop') }}
                    </a>
                </li>

                @if ($scope['type'] !== 'shop')
                    <li aria-hidden="true"><x-heroicon-m-chevron-right class="size-3" /></li>
                    <li class="truncate font-medium text-ink-800" aria-current="page">{{ $scope['title'] }}</li>
                @endif
            </ol>
        </div>
    </nav>

    <div class="hb-container py-6 sm:py-8">
        <div class="grid gap-6 lg:grid-cols-12 lg:gap-8">
            {{-- ============================================================
                 Filter sidebar
                 ============================================================ --}}
            <aside class="lg:col-span-3" x-data="{ open: false }">
                {{-- Mobile filter toggle --}}
                <button
                    type="button"
                    @click="open = !open"
                    class="mb-4 flex w-full items-center justify-between rounded-lg border border-ink-300 bg-white px-4 py-3 text-sm font-semibold text-ink-800 lg:hidden"
                    :aria-expanded="open"
                >
                    <span class="flex items-center gap-2">
                        <x-heroicon-o-adjustments-horizontal class="size-4" />
                        {{ __('hanbell.common.filters') }}
                    </span>
                    <x-heroicon-m-chevron-down class="size-4 transition" ::class="open && 'rotate-180'" />
                </button>

                <div
                    x-show="open || window.innerWidth >= 1024"
                    x-cloak
                    class="space-y-5 lg:!block"
                >
                    {{-- Active filter summary --}}
                    @if ($vendorIds !== [] || $genders !== [] || $minPrice !== null || $maxPrice !== null || $inStockOnly || $onSale || $featured)
                        <div class="rounded-xl border border-brand-600/20 bg-brand-50 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-bold uppercase tracking-wider text-brand-800">
                                    {{ __('hanbell.shop.active_filters') }}
                                </p>
                                <button
                                    type="button"
                                    wire:click="clearFilters"
                                    class="text-xs font-semibold text-brand-700 underline underline-offset-2 hover:text-brand-900"
                                >
                                    {{ __('hanbell.common.clear_all') }}
                                </button>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @if ($onSale)
                                    <x-ui.badge variant="brand" size="sm">{{ __('hanbell.shop.on_sale') }}</x-ui.badge>
                                @endif
                                @if ($inStockOnly)
                                    <x-ui.badge variant="brand" size="sm">{{ __('hanbell.shop.in_stock_only') }}</x-ui.badge>
                                @endif
                                @if ($featured)
                                    <x-ui.badge variant="brand" size="sm">{{ __('hanbell.product.featured_badge') }}</x-ui.badge>
                                @endif
                                @if ($minPrice !== null || $maxPrice !== null)
                                    <x-ui.badge variant="brand" size="sm">
                                        {{ $minPrice !== null ? \App\Support\Money::format($minPrice) : '₦0' }}
                                        –
                                        {{ $maxPrice !== null ? \App\Support\Money::format($maxPrice) : '∞' }}
                                    </x-ui.badge>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Categories within scope --}}
                    @if ($facets['categories']->isNotEmpty())
                        <div class="rounded-xl border border-ink-200 bg-white p-4">
                            <p class="mb-3 text-sm font-bold text-ink-900">{{ __('hanbell.nav.categories') }}</p>

                            <ul class="space-y-1">
                                @foreach ($facets['categories'] as $facetCategory)
                                    <li>
                                        <a
                                            href="{{ $facetCategory->publicUrl() }}"
                                            wire:navigate
                                            class="flex items-center justify-between gap-2 rounded-md px-2 py-1.5 text-sm text-ink-600 transition hover:bg-ink-50 hover:text-brand-700"
                                        >
                                            <span class="truncate">{{ $facetCategory->name }}</span>
                                            <span class="shrink-0 text-xs text-ink-400">{{ $facetCategory->products_count }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Shop for (gender) --}}
                    <div class="rounded-xl border border-ink-200 bg-white p-4">
                        <p class="mb-3 text-sm font-bold text-ink-900">{{ __('hanbell.shop.gender') }}</p>

                        <div class="space-y-2">
                            @foreach ($facets['genders'] as $value => $label)
                                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-ink-700">
                                    <input
                                        type="checkbox"
                                        wire:model.live="genders"
                                        value="{{ $value }}"
                                        class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600/30"
                                    >
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Brands --}}
                    @if ($facets['vendors'] !== [])
                        <div class="rounded-xl border border-ink-200 bg-white p-4">
                            <p class="mb-3 text-sm font-bold text-ink-900">{{ __('hanbell.vendor.vendors') }}</p>

                            <div class="max-h-64 space-y-2 overflow-y-auto pr-1">
                                @foreach ($facets['vendors'] as $vendor)
                                    <label class="flex cursor-pointer items-center gap-2.5 text-sm text-ink-700">
                                        <input
                                            type="checkbox"
                                            wire:model.live="vendorIds"
                                            value="{{ $vendor['id'] }}"
                                            class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600/30"
                                        >
                                        <span class="flex-1 truncate">{{ $vendor['name'] }}</span>
                                        <span class="shrink-0 text-xs text-ink-400">{{ $vendor['count'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Price --}}
                    <div class="rounded-xl border border-ink-200 bg-white p-4">
                        <p class="mb-3 text-sm font-bold text-ink-900">{{ __('hanbell.shop.price_range') }}</p>

                        <div class="flex items-center gap-2">
                            <label class="sr-only" for="min-price">{{ __('hanbell.shop.min_price') }}</label>
                            <input
                                id="min-price"
                                type="number"
                                min="0"
                                wire:model.blur="minPrice"
                                placeholder="{{ \App\Support\Money::format($priceBounds['min'], withDecimals: false) }}"
                                class="h-10 w-full rounded-lg border border-ink-300 px-3 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20"
                            >
                            <span class="text-ink-300">–</span>
                            <label class="sr-only" for="max-price">{{ __('hanbell.shop.max_price') }}</label>
                            <input
                                id="max-price"
                                type="number"
                                min="0"
                                wire:model.blur="maxPrice"
                                placeholder="{{ \App\Support\Money::format($priceBounds['max'], withDecimals: false) }}"
                                class="h-10 w-full rounded-lg border border-ink-300 px-3 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20"
                            >
                        </div>

                        <p class="mt-2 text-[11px] text-ink-400">
                            {{ __('hanbell.shop.min_price') }} {{ \App\Support\Money::format($priceBounds['min'], withDecimals: false) }}
                            · {{ __('hanbell.shop.max_price') }} {{ \App\Support\Money::format($priceBounds['max'], withDecimals: false) }}
                        </p>
                    </div>

                    {{-- Availability --}}
                    <div class="rounded-xl border border-ink-200 bg-white p-4">
                        <p class="mb-3 text-sm font-bold text-ink-900">{{ __('hanbell.shop.availability') }}</p>

                        <div class="space-y-2.5">
                            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-ink-700">
                                <input type="checkbox" wire:model.live="inStockOnly" class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600/30">
                                <span>{{ __('hanbell.shop.in_stock_only') }}</span>
                            </label>

                            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-ink-700">
                                <input type="checkbox" wire:model.live="onSale" class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600/30">
                                <span>{{ __('hanbell.shop.on_sale') }}</span>
                            </label>

                            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-ink-700">
                                <input type="checkbox" wire:model.live="featured" class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600/30">
                                <span>{{ __('hanbell.product.featured_badge') }}</span>
                            </label>
                        </div>
                    </div>

                    {{-- Sidebar ad slot --}}
                    <x-ads.slot :placement="AdPlacementKey::CategorySidebar" :limit="1" />
                </div>
            </aside>

            {{-- ============================================================
                 Results
                 ============================================================ --}}
            <div class="lg:col-span-9">
                <header class="mb-5 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-ink-950 sm:text-3xl">
                            {{ $scope['title'] }}
                        </h1>

                        @if ($scope['subtitle'])
                            <p class="mt-1.5 max-w-2xl text-sm text-ink-500">{{ $scope['subtitle'] }}</p>
                        @endif

                        <p class="mt-2 text-xs font-medium text-ink-400">
                            {{ trans_choice('hanbell.shop.results_count', $total, ['count' => number_format($total)]) }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <label for="sort" class="text-xs font-medium text-ink-500">{{ __('hanbell.shop.sort.label') }}</label>
                        <select
                            id="sort"
                            wire:model.live="sort"
                            class="h-10 rounded-lg border border-ink-300 bg-white pl-3 pr-8 text-sm font-medium text-ink-800 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20"
                        >
                            @foreach ([
                                'newest' => __('hanbell.shop.sort.newest'),
                                'popular' => __('hanbell.shop.sort.popular'),
                                'price_low' => __('hanbell.shop.sort.price_low'),
                                'price_high' => __('hanbell.shop.sort.price_high'),
                                'rating' => __('hanbell.shop.sort.rating'),
                                'discount' => __('hanbell.shop.sort.discount'),
                                'name' => __('hanbell.shop.sort.name'),
                            ] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </header>

                {{-- Top banner ad --}}
                <div class="mb-6">
                    <x-ads.slot :placement="AdPlacementKey::CategoryTopBanner" :limit="1" />
                </div>

                {{-- Live-updating region. `wire:loading` shows a skeleton rather
                     than blocking, so the page never appears frozen. --}}
                <div wire:loading.delay.long wire:target="search, sort, vendorIds, genders, minPrice, maxPrice, inStockOnly, onSale, featured">
                    <x-ui.skeleton :count="8" />
                </div>

                <div wire:loading.remove.delay.long wire:target="search, sort, vendorIds, genders, minPrice, maxPrice, inStockOnly, onSale, featured">
                    @if ($products->isEmpty())
                        <x-ui.empty-state
                            icon="magnifying-glass"
                            :title="__('hanbell.shop.no_products')"
                            :message="__('hanbell.shop.no_products_hint')"
                            :action-label="__('hanbell.shop.browse_all')"
                            :action-href="route('storefront.shop')"
                        />
                    @else
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5">
                            @foreach ($products as $index => $product)
                                {{-- The first row loads eagerly: it is what the
                                     shopper sees before scrolling. --}}
                                <x-product.card :product="$product" :eager="$index < 4" />
                            @endforeach
                        </div>

                        {{-- Infinite scroll.
                             The sentinel triggers loadMore() when it comes into
                             view; the button beneath is the accessible,
                             keyboard-reachable equivalent and is always present,
                             because an observer-only list cannot be used without
                             a pointer. --}}
                        @if ($hasMore)
                            <div class="mt-8 flex flex-col items-center gap-3">
                                <div wire:intersect="loadMore" class="h-1 w-full" aria-hidden="true"></div>

                                <x-ui.button
                                    wire:click="loadMore"
                                    variant="outline"
                                    size="lg"
                                    loading="loadMore"
                                >
                                    <span wire:loading.remove wire:target="loadMore">{{ __('hanbell.common.load_more') }}</span>
                                    <span wire:loading wire:target="loadMore">{{ __('hanbell.common.loading') }}</span>
                                </x-ui.button>

                                <p class="text-xs text-ink-400">
                                    {{ __('hanbell.common.showing') }} {{ $products->count() }} {{ __('hanbell.common.of') }} {{ number_format($total) }}
                                </p>
                            </div>
                        @else
                            <p class="mt-8 text-center text-xs text-ink-400">{{ __('hanbell.common.no_more') }}</p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
