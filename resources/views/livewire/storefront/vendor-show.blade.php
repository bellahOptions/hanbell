<div>
    {{-- Brand header --}}
    <section class="relative overflow-hidden bg-ink-950 py-10 text-white sm:py-14">
        <div class="absolute inset-0 bg-[radial-gradient(50rem_25rem_at_10%_-20%,rgba(23,133,8,0.5),transparent_60%)]"></div>

        <div class="hb-container relative">
            <div class="flex flex-wrap items-start gap-6">
                @if ($vendor->logo_path)
                    <img
                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($vendor->logo_path) }}"
                        alt="{{ $vendor->name }}"
                        class="size-20 rounded-2xl object-cover ring-2 ring-white/10 sm:size-24"
                    >
                @else
                    <span class="flex size-20 items-center justify-center rounded-2xl bg-accent-300 text-2xl font-extrabold text-ink-950 sm:size-24">
                        {{ \Illuminate\Support\Str::substr($vendor->name, 0, 2) }}
                    </span>
                @endif

                <div class="min-w-0 flex-1">
                    <p class="hb-eyebrow mb-2 text-accent-300">{{ __('hanbell.legal.nigerian_made') }}</p>

                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-4xl">{{ $vendor->name }}</h1>

                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink-300">
                        <span class="flex items-center gap-1.5">
                            <x-heroicon-o-map-pin class="size-4" />
                            {{ $vendor->location() }}
                        </span>

                        <span class="flex items-center gap-1.5">
                            <x-heroicon-o-squares-2x2 class="size-4" />
                            {{ trans_choice('hanbell.vendor.products_count', $products->total(), ['count' => $products->total()]) }}
                        </span>

                        @if ($vendor->website)
                            <a
                                href="{{ $vendor->website }}"
                                target="_blank"
                                rel="noopener noreferrer nofollow"
                                class="flex items-center gap-1.5 transition hover:text-accent-300"
                            >
                                <x-heroicon-o-link class="size-4" />
                                {{ \Illuminate\Support\Str::limit(preg_replace('#^https?://#', '', $vendor->website), 30) }}
                            </a>
                        @endif
                    </div>

                    @if ($vendor->description)
                        <p class="mt-4 max-w-2xl text-sm leading-relaxed text-ink-300">{{ $vendor->description }}</p>
                    @endif
                </div>
            </div>

            {{-- Brand story --}}
            @if ($vendor->story)
                <details class="group mt-6 max-w-2xl rounded-xl border border-white/10 bg-white/5 p-4">
                    <summary class="cursor-pointer list-none text-sm font-semibold text-accent-300 marker:hidden">
                        <span class="flex items-center gap-2">
                            <x-heroicon-o-book-open class="size-4" />
                            {{ __('hanbell.vendor.brand_story') }}
                            <x-heroicon-m-chevron-down class="size-4 transition group-open:rotate-180" />
                        </span>
                    </summary>
                    <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ink-300">{{ $vendor->story }}</p>
                </details>
            @endif
        </div>
    </section>

    {{-- Catalogue --}}
    <div class="hb-container py-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-lg font-bold tracking-tight text-ink-950">{{ __('hanbell.product.products') }}</h2>

            <div class="flex items-center gap-2">
                <label for="brand-sort" class="text-xs font-medium text-ink-500">{{ __('hanbell.common.sort_by') }}</label>
                <select
                    id="brand-sort"
                    wire:model.live="sort"
                    class="h-10 rounded-lg border border-ink-300 bg-white pl-3 pr-8 text-sm font-medium focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20"
                >
                    <option value="newest">{{ __('hanbell.shop.sort.newest') }}</option>
                    <option value="popular">{{ __('hanbell.shop.sort.popular') }}</option>
                    <option value="price_low">{{ __('hanbell.shop.sort.price_low') }}</option>
                    <option value="price_high">{{ __('hanbell.shop.sort.price_high') }}</option>
                </select>
            </div>
        </div>

        @if ($products->isEmpty())
            <x-ui.empty-state
                icon="tag"
                :title="__('hanbell.shop.no_products')"
                :message="__('hanbell.shop.no_products_hint')"
                :action-label="__('hanbell.shop.browse_all')"
                :action-href="route('storefront.shop')"
            />
        @else
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-product.card :product="$product" :eager="$loop->index < 4" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
