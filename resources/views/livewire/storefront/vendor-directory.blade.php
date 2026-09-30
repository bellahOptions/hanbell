<div>
<div class="border-b border-ink-100 bg-ink-950 py-12 text-white">
    <div class="hb-container">
        <p class="hb-eyebrow mb-3 text-accent-300">{{ __('hanbell.legal.nigerian_made') }}</p>

        <h1 class="max-w-2xl text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">
            {{ __('hanbell.vendor.directory_title') }}
        </h1>

        <p class="mt-4 max-w-xl text-sm text-ink-300 sm:text-base">
            {{ __('hanbell.vendor.directory_subtitle') }}
        </p>
    </div>
</div>

<div class="hb-container py-8">
    {{-- Controls --}}
    <div class="mb-7 flex flex-wrap items-center gap-3">
        <div class="relative min-w-0 flex-1 sm:max-w-sm">
            <label for="vendor-search" class="sr-only">{{ __('hanbell.search.label') }}</label>
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-ink-400" />
            <input
                id="vendor-search"
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('hanbell.search.placeholder') }}"
                class="h-11 w-full rounded-lg border border-ink-300 bg-white pl-10 pr-4 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20"
            >
        </div>

        <div class="flex items-center gap-2">
            <label for="vendor-sort" class="text-xs font-medium text-ink-500">{{ __('hanbell.common.sort_by') }}</label>
            <select
                id="vendor-sort"
                wire:model.live="sort"
                class="h-11 rounded-lg border border-ink-300 bg-white pl-3 pr-8 text-sm font-medium focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20"
            >
                <option value="featured">{{ __('hanbell.product.featured_badge') }}</option>
                <option value="products">{{ __('hanbell.product.products') }}</option>
                <option value="name">{{ __('hanbell.common.name') }}</option>
                <option value="newest">{{ __('hanbell.shop.sort.newest') }}</option>
            </select>
        </div>
    </div>

    <div wire:loading.delay.long wire:target="search, sort">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @for ($i = 0; $i < 6; $i++)
                <div class="rounded-xl border border-ink-200 bg-white p-5">
                    <div class="flex items-center gap-4">
                        <div class="hb-skeleton size-14 rounded-xl"></div>
                        <div class="flex-1 space-y-2">
                            <div class="hb-skeleton h-4 w-2/3 rounded-full"></div>
                            <div class="hb-skeleton h-3 w-1/3 rounded-full"></div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>
    </div>

    <div wire:loading.remove.delay.long wire:target="search, sort">
        @if ($vendors->isEmpty())
            <x-ui.empty-state
                icon="building-storefront"
                :title="__('hanbell.vendor.no_vendors')"
                :action-label="__('hanbell.shop.browse_all')"
                :action-href="route('storefront.shop')"
            />
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($vendors as $vendor)
                    <a
                        href="{{ $vendor->publicUrl() }}"
                        wire:navigate
                        class="group flex flex-col rounded-xl border border-ink-200 bg-white p-5 transition hover:border-brand-600/40 hover:shadow-card"
                    >
                        <div class="flex items-start gap-4">
                            @if ($vendor->logo_path)
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($vendor->logo_path) }}"
                                    alt="{{ $vendor->name }}"
                                    loading="lazy"
                                    class="size-14 shrink-0 rounded-xl object-cover"
                                >
                            @else
                                <span class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-lg font-extrabold text-white">
                                    {{ \Illuminate\Support\Str::substr($vendor->name, 0, 2) }}
                                </span>
                            @endif

                            <div class="min-w-0 flex-1">
                                <h2 class="clamp-1 text-sm font-bold text-ink-950 transition group-hover:text-brand-700">
                                    {{ $vendor->name }}
                                </h2>
                                <p class="mt-0.5 flex items-center gap-1 text-xs text-ink-500">
                                    <x-heroicon-o-map-pin class="size-3.5" />
                                    {{ $vendor->location() }}
                                </p>
                            </div>
                        </div>

                        @if ($vendor->description)
                            <p class="clamp-2 mt-3 text-xs leading-relaxed text-ink-500">{{ $vendor->description }}</p>
                        @endif

                        <div class="mt-auto flex items-center justify-between gap-2 pt-4">
                            <span class="text-xs font-semibold text-ink-500">
                                {{ trans_choice('hanbell.vendor.products_count', $vendor->products_count, ['count' => $vendor->products_count]) }}
                            </span>

                            <span class="flex items-center gap-1 text-xs font-bold text-brand-700">
                                {{ __('hanbell.vendor.visit') }}
                                <x-heroicon-m-arrow-right class="size-3.5 transition-transform group-hover:translate-x-0.5" />
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $vendors->links() }}
            </div>
        @endif
    </div>

    {{-- CTA to apply --}}
    <div class="mt-12 overflow-hidden rounded-2xl bg-ink-950 p-8 text-white sm:p-10">
        <div class="flex flex-wrap items-center justify-between gap-6">
            <div class="max-w-lg">
                <h2 class="text-xl font-bold tracking-tight sm:text-2xl">{{ __('hanbell.vendor.apply_title') }}</h2>
                <p class="mt-2 text-sm text-ink-300">{{ __('hanbell.vendor.apply_subtitle') }}</p>
            </div>

            <x-ui.button :href="route('storefront.vendors.apply')" variant="accent" size="lg">
                {{ __('hanbell.nav.sell_with_us') }}
                <x-heroicon-m-arrow-right class="size-4" />
            </x-ui.button>
        </div>
    </div>
</div>
</div>
