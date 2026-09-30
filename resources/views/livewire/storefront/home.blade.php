@php
    use App\Enums\AdPlacementKey;
@endphp

<div>
    {{-- ============================================================
         Hero. Left category rail + dominant hero + right ad rail,
         the layout Nigerian shoppers already read fluently.
         ============================================================ --}}
    <section class="border-b border-ink-100 bg-ink-50/60 py-5 sm:py-7">
        <div class="hb-container">
            <div class="grid gap-4 lg:grid-cols-12">
                {{-- Category rail --}}
                <aside class="hidden lg:col-span-3 lg:block" aria-label="{{ __('hanbell.nav.departments') }}">
                    <div class="h-full overflow-hidden rounded-xl border border-ink-200 bg-white">
                        <p class="hb-eyebrow border-b border-ink-100 px-4 py-3 text-ink-500">
                            {{ __('hanbell.nav.departments') }}
                        </p>

                        <ul class="py-1.5">
                            @forelse ($departments as $department)
                                <li>
                                    <a
                                        href="{{ $department->publicUrl() }}"
                                        wire:navigate
                                        class="group flex items-center justify-between gap-2 px-4 py-2 text-sm font-medium text-ink-700 transition hover:bg-brand-50 hover:text-brand-700"
                                    >
                                        <span class="flex items-center gap-2.5 truncate">
                                            <x-heroicon-o-tag class="size-4 shrink-0 text-ink-300 transition group-hover:text-brand-600" />
                                            <span class="truncate">{{ $department->name }}</span>
                                        </span>
                                        <x-heroicon-m-chevron-right class="size-3.5 shrink-0 text-ink-300 transition group-hover:translate-x-0.5 group-hover:text-brand-600" />
                                    </a>
                                </li>
                            @empty
                                <li class="px-4 py-3 text-sm text-ink-400">{{ __('hanbell.common.no_items') }}</li>
                            @endforelse
                        </ul>
                    </div>
                </aside>

                {{-- Hero: the ad slot keeps its shape even when nothing is booked,
                     because an empty 20:9 hole collapses the whole row. --}}
                <div class="lg:col-span-6">
                    <x-ads.slot :placement="AdPlacementKey::HomeHero" :fallback="true" :limit="1">
                        <div class="relative aspect-[16/9] overflow-hidden rounded-xl bg-ink-950 lg:aspect-[20/9]">
                            <div class="absolute inset-0 bg-[radial-gradient(60rem_30rem_at_20%_-10%,rgba(23,133,8,0.55),transparent_60%),radial-gradient(40rem_20rem_at_100%_110%,rgba(255,242,0,0.18),transparent_60%)]"></div>

                            <div class="relative flex h-full flex-col justify-center gap-3 p-6 sm:p-10">
                                <p class="hb-eyebrow text-accent-300">
                                    {{ __('hanbell.legal.nigerian_made') }}
                                </p>

                                <h1 class="max-w-lg text-3xl font-extrabold leading-[1.1] tracking-tight text-white sm:text-4xl lg:text-5xl">
                                    {{ __('hanbell.brand.tagline') }}
                                </h1>

                                <p class="max-w-md text-sm text-ink-300 sm:text-base">
                                    {{ __('hanbell.brand.description') }}
                                </p>

                                <div class="mt-2 flex flex-wrap gap-2.5">
                                    <x-ui.button :href="route('storefront.shop')" variant="accent" size="lg">
                                        {{ __('hanbell.nav.shop') }}
                                        <x-heroicon-m-arrow-right class="size-4" />
                                    </x-ui.button>

                                    <x-ui.button
                                        :href="route('storefront.vendors.apply')"
                                        variant="outline"
                                        size="lg"
                                        class="border-white/25 bg-white/10 text-white hover:border-white/40 hover:bg-white/15"
                                    >
                                        {{ __('hanbell.nav.sell_with_us') }}
                                    </x-ui.button>
                                </div>
                            </div>
                        </div>
                    </x-ads.slot>
                </div>

                {{-- Right rail --}}
                <div class="hidden lg:col-span-3 lg:block">
                    <x-ads.slot :placement="AdPlacementKey::HomeSidebar" :fallback="true" :limit="2">
                        <div class="flex h-full flex-col gap-4">
                            <div class="flex flex-1 flex-col justify-center rounded-xl border border-brand-600/20 bg-brand-50 p-5">
                                <x-heroicon-o-truck class="mb-3 size-7 text-brand-600" />
                                <p class="text-sm font-bold text-brand-900">
                                    {{ __('hanbell.legal.nationwide_delivery') }}
                                </p>
                                <p class="mt-1 text-xs text-brand-800/80">
                                    {{ __('hanbell.legal.nationwide_delivery_hint') }}
                                </p>
                            </div>

                            <div class="flex flex-1 flex-col justify-center rounded-xl border border-ink-200 bg-white p-5">
                                <x-heroicon-o-shield-check class="mb-3 size-7 text-ink-400" />
                                <p class="text-sm font-bold text-ink-900">
                                    {{ __('hanbell.legal.secure_checkout') }}
                                </p>
                                <p class="mt-1 text-xs text-ink-500">
                                    {{ __('hanbell.legal.secure_checkout_hint') }}
                                </p>
                            </div>
                        </div>
                    </x-ads.slot>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         Trust strip
         ============================================================ --}}
    <section class="border-b border-ink-100 bg-white py-6">
        <div class="hb-container">
            <ul class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach ([
                    ['icon' => 'sparkles', 'title' => __('hanbell.legal.nigerian_made'), 'hint' => __('hanbell.legal.nigerian_made_hint')],
                    ['icon' => 'banknotes', 'title' => __('hanbell.legal.fair_prices'), 'hint' => __('hanbell.legal.fair_prices_hint')],
                    ['icon' => 'truck', 'title' => __('hanbell.legal.nationwide_delivery'), 'hint' => __('hanbell.legal.nationwide_delivery_hint')],
                    ['icon' => 'arrow-path-rounded-square', 'title' => __('hanbell.legal.easy_returns'), 'hint' => __('hanbell.legal.easy_returns_hint')],
                ] as $item)
                    <li class="flex items-start gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                            <x-dynamic-component :component="'heroicon-o-'.$item['icon']" class="size-4.5" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-ink-900">{{ $item['title'] }}</p>
                            <p class="mt-0.5 clamp-2 text-[11px] leading-snug text-ink-500">{{ $item['hint'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ============================================================
         Promo strip ad slot
         ============================================================ --}}
    @php $stripAds = app(\App\Services\Advertising\AdServer::class)->fill(AdPlacementKey::HomeStrip, 1); @endphp
    @if ($stripAds->isNotEmpty())
        <div class="hb-container pt-8">
            <x-ads.slot :placement="AdPlacementKey::HomeStrip" :limit="1" />
        </div>
    @endif

    {{-- ============================================================
         Deals rail
         ============================================================ --}}
    @if ($dealProducts->isNotEmpty())
        <x-ui.section
            :title="__('hanbell.shop.on_sale')"
            :eyebrow="__('hanbell.legal.fair_prices')"
            :subtitle="__('hanbell.legal.fair_prices_hint')"
            :href="route('storefront.shop', ['on_sale' => 1])"
        >
            <div class="hb-rail -mx-1 px-1 pb-2">
                @foreach ($dealProducts as $product)
                    <div class="w-[46%] shrink-0 sm:w-[31%] lg:w-[19.2%]">
                        <x-product.card :product="$product" />
                    </div>
                @endforeach
            </div>
        </x-ui.section>
    @endif

    {{-- ============================================================
         New arrivals
         ============================================================ --}}
    @if ($newArrivals->isNotEmpty())
        <x-ui.section
            :title="__('hanbell.nav.new_arrivals')"
            :subtitle="__('hanbell.legal.approved_quality')"
            :href="route('storefront.shop', ['sort' => 'newest'])"
        >
            <div class="hb-rail -mx-1 px-1 pb-2">
                @foreach ($newArrivals as $product)
                    <div class="w-[46%] shrink-0 sm:w-[31%] lg:w-[19.2%]">
                        <x-product.card :product="$product" />
                    </div>
                @endforeach
            </div>
        </x-ui.section>
    @endif

    {{-- ============================================================
         Mid-page banner ad
         ============================================================ --}}
    <div class="hb-container py-4">
        <x-ads.slot :placement="AdPlacementKey::HomeMidBanner" :limit="1" />
    </div>

    {{-- ============================================================
         Per-department rails
         ============================================================ --}}
    @foreach ($categoryRails as $rail)
        <x-ui.section
            :title="$rail['department']->name"
            :href="$rail['department']->publicUrl()"
            spacing="sm"
        >
            <div class="hb-rail -mx-1 px-1 pb-2">
                @foreach ($rail['products'] as $product)
                    <div class="w-[46%] shrink-0 sm:w-[31%] lg:w-[19.2%]">
                        <x-product.card :product="$product" />
                    </div>
                @endforeach
            </div>
        </x-ui.section>
    @endforeach

    {{-- ============================================================
         Trending
         ============================================================ --}}
    @if ($trending->isNotEmpty())
        <x-ui.section
            :title="__('hanbell.nav.trending')"
            :subtitle="__('hanbell.shop.sort.popular')"
            :href="route('storefront.shop', ['sort' => 'popular'])"
        >
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($trending as $product)
                    <x-product.card :product="$product" />
                @endforeach
            </div>
        </x-ui.section>
    @endif

    {{-- ============================================================
         Brand spotlight — the marketplace's actual differentiator,
         so it gets a full band rather than a sidebar mention.
         ============================================================ --}}
    @if ($featuredVendors->isNotEmpty())
        <section class="bg-ink-950 py-14 text-white">
            <div class="hb-container">
                <header class="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="hb-eyebrow mb-2 text-accent-300">{{ __('hanbell.vendor.directory_subtitle') }}</p>
                        <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                            {{ __('hanbell.vendor.directory_title') }}
                        </h2>
                    </div>

                    <a
                        href="{{ route('storefront.vendors.index') }}"
                        wire:navigate
                        class="group inline-flex items-center gap-1.5 text-sm font-semibold text-accent-300 transition hover:text-accent-200"
                    >
                        {{ __('hanbell.common.view_all') }}
                        <x-heroicon-m-arrow-right class="size-4 transition-transform group-hover:translate-x-0.5" />
                    </a>
                </header>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($featuredVendors as $vendor)
                        <a
                            href="{{ $vendor->publicUrl() }}"
                            wire:navigate
                            class="group flex flex-col items-center gap-3 rounded-xl border border-white/10 bg-white/5 p-4 text-center transition hover:border-accent-300/40 hover:bg-white/10"
                        >
                            @if ($vendor->logo_path)
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($vendor->logo_path) }}"
                                    alt="{{ $vendor->name }}"
                                    loading="lazy"
                                    class="size-14 rounded-full object-cover ring-2 ring-white/10"
                                >
                            @else
                                <span class="flex size-14 items-center justify-center rounded-full bg-accent-300 text-lg font-extrabold text-ink-950">
                                    {{ \Illuminate\Support\Str::substr($vendor->name, 0, 2) }}
                                </span>
                            @endif

                            <span class="w-full">
                                <span class="clamp-1 block text-sm font-semibold text-white">{{ $vendor->name }}</span>
                                <span class="mt-0.5 block text-[11px] text-ink-400">
                                    {{ trans_choice('hanbell.vendor.products_count', $vendor->products_count, ['count' => $vendor->products_count]) }}
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============================================================
         Footer banner ad
         ============================================================ --}}
    <div class="hb-container pt-10">
        <x-ads.slot :placement="AdPlacementKey::FooterBanner" :limit="1" />
    </div>
</div>
