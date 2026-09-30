@php
    use App\Enums\AdPlacementKey;

    $media = $product->media;
    $activeMedia = $media->get($activeImage) ?? $media->first();
    $inStock = $product->isInStock($selectedVariant);
    $discount = $product->discountPercent($selectedVariant);
    $available = $product->availableQuantity($selectedVariant);
@endphp

<div>
    {{-- Breadcrumb --}}
    <nav class="border-b border-ink-100 bg-ink-50/60" aria-label="Breadcrumb">
        <div class="hb-container">
            <ol class="flex flex-wrap items-center gap-1.5 py-3 text-xs text-ink-500">
                <li><a href="{{ route('storefront.home') }}" wire:navigate class="transition hover:text-brand-700">{{ __('hanbell.nav.home') }}</a></li>
                <li aria-hidden="true"><x-heroicon-m-chevron-right class="size-3" /></li>

                @if ($product->category)
                    @if ($product->category->department)
                        <li>
                            <a href="{{ $product->category->department->publicUrl() }}" wire:navigate class="transition hover:text-brand-700">
                                {{ $product->category->department->name }}
                            </a>
                        </li>
                        <li aria-hidden="true"><x-heroicon-m-chevron-right class="size-3" /></li>
                    @endif
                    <li>
                        <a href="{{ $product->category->publicUrl() }}" wire:navigate class="transition hover:text-brand-700">
                            {{ $product->category->name }}
                        </a>
                    </li>
                    <li aria-hidden="true"><x-heroicon-m-chevron-right class="size-3" /></li>
                @endif

                <li class="clamp-1 max-w-[16rem] font-medium text-ink-800" aria-current="page">{{ $product->name }}</li>
            </ol>
        </div>
    </nav>

    <div class="hb-container py-6 sm:py-10">
        <div class="grid gap-8 lg:grid-cols-12 lg:gap-10">
            {{-- ============================================================
                 Gallery
                 ============================================================ --}}
            <div class="lg:col-span-7">
                <div class="flex flex-col-reverse gap-3 sm:flex-row">
                    {{-- Thumbnails --}}
                    @if ($media->count() > 1)
                        <div class="flex gap-2 overflow-x-auto sm:w-20 sm:flex-col sm:overflow-visible">
                            @foreach ($media as $index => $item)
                                <button
                                    type="button"
                                    wire:click="setImage({{ $index }})"
                                    @class([
                                        'relative shrink-0 overflow-hidden rounded-lg ring-2 transition',
                                        'ring-brand-600' => $index === $activeImage,
                                        'ring-transparent hover:ring-ink-300' => $index !== $activeImage,
                                    ])
                                    aria-label="{{ __('hanbell.common.details') }} {{ $index + 1 }}"
                                >
                                    <span class="hb-frame block size-16 rounded-lg sm:size-20">
                                        <img src="{{ $item->url() }}" alt="" loading="lazy" class="object-cover">
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Main image --}}
                    <div class="relative flex-1">
                        <div class="hb-frame aspect-[3/4] w-full rounded-2xl ring-1 ring-ink-200">
                            @if ($activeMedia)
                                <img
                                    src="{{ $activeMedia->url() }}"
                                    alt="{{ $activeMedia->alt() }}"
                                    loading="eager"
                                    fetchpriority="high"
                                    width="900"
                                    height="1200"
                                >
                            @else
                                <img src="{{ \App\Models\Product::placeholderImageUrl() }}" alt="{{ $product->name }}">
                            @endif

                            {{-- Flags --}}
                            <div class="pointer-events-none absolute left-3 top-3 z-10 flex flex-col items-start gap-2">
                                @if ($discount)
                                    <span class="rounded-md bg-accent-300 px-2.5 py-1 text-xs font-extrabold text-ink-950 shadow-sm">
                                        {{ __('hanbell.product.discount_badge', ['percent' => $discount]) }}
                                    </span>
                                @endif

                                @if ($product->is_handmade)
                                    <span class="rounded-md bg-ink-950/90 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-white backdrop-blur-sm">
                                        {{ __('hanbell.product.handmade') }}
                                    </span>
                                @endif
                            </div>

                            {{-- Wishlist --}}
                            @unless (auth()->user()?->isAdmin())
                                <livewire:storefront.toggle-wishlist
                                    :product-id="$product->id"
                                    :variant-id="$selectedVariantId"
                                    :key="'wish-show-'.$product->id"
                                />
                            @endunless
                        </div>

                        {{-- Unsplash attribution, as required by their licence. --}}
                        @if ($activeMedia?->credit_name)
                            <p class="mt-2 text-[11px] text-ink-400">
                                {{ __('hanbell.product.description') }}:
                                <a
                                    href="{{ $activeMedia->credit_url }}"
                                    target="_blank"
                                    rel="noopener noreferrer nofollow"
                                    class="underline underline-offset-2 hover:text-ink-600"
                                >{{ $activeMedia->credit_name }}</a>
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ============================================================
                 Buy column
                 ============================================================ --}}
            <div class="lg:col-span-5">
                @if ($product->vendor)
                    <a
                        href="{{ $product->vendor->publicUrl() }}"
                        wire:navigate
                        class="mb-2 inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-brand-700 transition hover:text-brand-800"
                    >
                        <x-heroicon-o-building-storefront class="size-3.5" />
                        {{ $product->vendor->name }}
                    </a>
                @endif

                <h1 class="text-2xl font-extrabold leading-tight tracking-tight text-ink-950 sm:text-3xl">
                    {{ $product->translated('name') }}
                </h1>

                @if ($product->rating_count > 0)
                    <div class="mt-2.5 flex items-center gap-2">
                        <x-ui.stars :rating="$product->rating_average" :count="$product->rating_count" size="sm" />
                        <a href="#reviews" class="text-xs font-medium text-brand-700 underline underline-offset-2">
                            {{ trans_choice('hanbell.product.reviews_tab', $product->rating_count) }}
                        </a>
                    </div>
                @endif

                @if ($product->summary)
                    <p class="mt-3 text-sm leading-relaxed text-ink-600">{{ $product->summary }}</p>
                @endif

                {{-- Price --}}
                <div class="mt-5 flex flex-wrap items-baseline gap-3">
                    <x-ui.price
                        :amount="$product->priceFor($selectedVariant)"
                        :compare-at="$product->compareAtPriceFor($selectedVariant)"
                        :currency="$product->currency"
                        size="xl"
                    />

                    @if ($discount)
                        <x-ui.badge variant="accent" size="md">
                            {{ __('hanbell.product.save_percent', ['percent' => $discount]) }}
                        </x-ui.badge>
                    @endif
                </div>

                {{-- Stock state --}}
                <p class="mt-2.5 flex items-center gap-1.5 text-sm font-medium">
                    @if ($inStock && $available <= 5)
                        <x-heroicon-s-bolt class="size-4 text-warning-500" />
                        <span class="text-warning-600">{{ __('hanbell.product.low_stock', ['count' => $available]) }}</span>
                    @elseif ($inStock)
                        <x-heroicon-s-check-circle class="size-4 text-brand-600" />
                        <span class="text-brand-700">{{ __('hanbell.product.in_stock') }}</span>
                    @else
                        <x-heroicon-s-x-circle class="size-4 text-danger-500" />
                        <span class="text-danger-600">{{ __('hanbell.product.out_of_stock') }}</span>
                    @endif

                    @if ($product->made_in_city)
                        <span class="text-ink-300">·</span>
                        <span class="flex items-center gap-1 text-ink-500">
                            <x-heroicon-o-map-pin class="size-3.5" />
                            {{ __('hanbell.product.made_in') }} {{ $product->made_in_city }}
                        </span>
                    @endif
                </p>

                {{-- Size options --}}
                @php
                    $sizeVariants = $product->variants->where('is_active', true)->filter(fn ($v) => filled($v->size));
                    $colorVariants = $product->variants->where('is_active', true)->filter(fn ($v) => filled($v->color));
                @endphp

                @if ($sizeVariants->isNotEmpty())
                    <div class="mt-6">
                        <div class="mb-2.5 flex items-center justify-between">
                            <p class="text-sm font-bold text-ink-900">{{ __('hanbell.product.choose_size') }}</p>
                            <a href="{{ route('storefront.pages.show', ['slug' => 'size-guide']) }}" wire:navigate class="text-xs font-semibold text-brand-700 underline underline-offset-2">
                                {{ __('hanbell.product.size_guide') }}
                            </a>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @foreach ($sizeVariants as $variant)
                                @php $variantAvailable = $variant->availableQuantity() > 0; @endphp

                                <button
                                    type="button"
                                    wire:click="selectVariant({{ $variant->id }})"
                                    @disabled(! $variantAvailable)
                                    @class([
                                        'flex h-10 min-w-12 items-center justify-center rounded-lg border px-3 text-sm font-semibold transition',
                                        'border-ink-950 bg-ink-950 text-white' => $selectedVariantId === $variant->id,
                                        'border-ink-300 bg-white text-ink-800 hover:border-ink-500' => $selectedVariantId !== $variant->id && $variantAvailable,
                                        'cursor-not-allowed border-ink-200 bg-ink-50 text-ink-300 line-through' => ! $variantAvailable,
                                    ])
                                    @if ($selectedVariantId === $variant->id) aria-pressed="true" @endif
                                >
                                    {{ $variant->size }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Colour options --}}
                @if ($colorVariants->isNotEmpty())
                    <div class="mt-6">
                        <p class="mb-2.5 text-sm font-bold text-ink-900">{{ __('hanbell.product.choose_color') }}</p>

                        <div class="flex flex-wrap gap-2">
                            @foreach ($colorVariants as $variant)
                                @php $variantAvailable = $variant->availableQuantity() > 0; @endphp

                                <button
                                    type="button"
                                    wire:click="selectVariant({{ $variant->id }})"
                                    @disabled(! $variantAvailable)
                                    @class([
                                        'flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition',
                                        'border-ink-950 bg-ink-950 text-white' => $selectedVariantId === $variant->id,
                                        'border-ink-300 bg-white text-ink-800 hover:border-ink-500' => $selectedVariantId !== $variant->id && $variantAvailable,
                                        'cursor-not-allowed border-ink-200 bg-ink-50 text-ink-300' => ! $variantAvailable,
                                    ])
                                    @if ($selectedVariantId === $variant->id) aria-pressed="true" @endif
                                >
                                    @if ($variant->color_hex)
                                        <span
                                            class="size-4 rounded-full ring-1 ring-inset ring-black/15"
                                            style="background-color: {{ $variant->color_hex }}"
                                            aria-hidden="true"
                                        ></span>
                                    @endif
                                    {{ $variant->color }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Add to bag --}}
                <div class="mt-7">
                    <livewire:storefront.add-to-cart
                        :product-id="$product->id"
                        :variant-id="$selectedVariantId"
                        :key="'add-'.$product->id.'-'.($selectedVariantId ?? 'none')"
                    />
                </div>

                {{-- Trust points --}}
                <ul class="mt-6 grid grid-cols-2 gap-3 border-t border-ink-100 pt-6">
                    @foreach ([
                        ['icon' => 'truck', 'text' => __('hanbell.shipping.nationwide')],
                        ['icon' => 'shield-check', 'text' => __('hanbell.legal.secure_checkout')],
                        ['icon' => 'arrow-path-rounded-square', 'text' => __('hanbell.legal.easy_returns')],
                        ['icon' => 'sparkles', 'text' => __('hanbell.product.made_in_nigeria')],
                    ] as $point)
                        <li class="flex items-start gap-2 text-xs font-medium text-ink-600">
                            <x-dynamic-component :component="'heroicon-o-'.$point['icon']" class="mt-0.5 size-4 shrink-0 text-brand-600" />
                            <span>{{ $point['text'] }}</span>
                        </li>
                    @endforeach
                </ul>

                {{-- Product sidebar ad --}}
                <div class="mt-6">
                    <x-ads.slot :placement="AdPlacementKey::ProductSidebar" :limit="1" />
                </div>
            </div>
        </div>

        {{-- ============================================================
             Details
             ============================================================ --}}
        <div class="mt-12 grid gap-8 lg:grid-cols-12" x-data="{ tab: 'description' }">
            <div class="lg:col-span-8">
                <div class="flex gap-1 border-b border-ink-200" role="tablist">
                    @foreach ([
                        'description' => __('hanbell.product.description'),
                        'details' => __('hanbell.product.details_tab'),
                        'shipping' => __('hanbell.product.shipping_tab'),
                        'reviews' => __('hanbell.product.reviews_tab'),
                    ] as $key => $label)
                        <button
                            type="button"
                            @click="tab = '{{ $key }}'"
                            role="tab"
                            :aria-selected="tab === '{{ $key }}'"
                            :class="tab === '{{ $key }}'
                                ? 'border-b-2 border-brand-600 text-brand-700 font-bold'
                                : 'border-b-2 border-transparent text-ink-500 hover:text-ink-800 font-medium'"
                            class="-mb-px px-4 py-3 text-sm transition"
                        >
                            {{ $label }}
                            @if ($key === 'reviews' && $product->rating_count > 0)
                                <span class="ml-1 text-xs text-ink-400">({{ $product->rating_count }})</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                <div class="pt-6">
                    {{-- Description --}}
                    <div x-show="tab === 'description'" role="tabpanel">
                        @if ($product->description)
                            <div class="prose-sm max-w-none text-sm leading-relaxed text-ink-700">
                                {!! nl2br(e($product->translated('description'))) !!}
                            </div>
                        @else
                            <p class="text-sm text-ink-500">{{ __('hanbell.common.no_items') }}</p>
                        @endif

                        @if ($product->tags->isNotEmpty())
                            <div class="mt-5 flex flex-wrap gap-2">
                                @foreach ($product->tags as $tag)
                                    <a href="{{ $tag->publicUrl() }}" wire:navigate class="rounded-full bg-ink-100 px-3 py-1 text-xs font-medium text-ink-600 transition hover:bg-brand-50 hover:text-brand-700">
                                        {{ $tag->displayName() }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Details --}}
                    <div x-show="tab === 'details'" x-cloak role="tabpanel">
                        <dl class="divide-y divide-ink-100 text-sm">
                            @foreach (array_filter([
                                __('hanbell.product.sku') => $product->sku,
                                __('hanbell.product.material') => $product->material,
                                __('hanbell.product.category') => $product->category?->name,
                                __('hanbell.product.brand') => $product->brand?->name ?? $product->vendor?->name,
                                __('hanbell.product.made_in') => $product->made_in_city ? $product->made_in_city.', Nigeria' : __('hanbell.product.made_in_nigeria'),
                                __('hanbell.product.care_instructions') => $product->care_instructions,
                            ]) as $label => $value)
                                <div class="flex items-start justify-between gap-6 py-3">
                                    <dt class="shrink-0 font-medium text-ink-500">{{ $label }}</dt>
                                    <dd class="text-right font-semibold text-ink-900">{{ $value }}</dd>
                                </div>
                            @endforeach

                            @foreach ($product->attributes as $attribute)
                                <div class="flex items-start justify-between gap-6 py-3">
                                    <dt class="shrink-0 font-medium text-ink-500">{{ $attribute->name }}</dt>
                                    <dd class="text-right font-semibold text-ink-900">
                                        {{ $product->attributes->where('id', $attribute->id)->pluck('pivot.attribute_value_id')->filter()
                                            ->map(fn ($id) => \App\Models\AttributeValue::find($id)?->value)->filter()->implode(', ') }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>

                    {{-- Shipping --}}
                    <div x-show="tab === 'shipping'" x-cloak role="tabpanel" class="space-y-4 text-sm text-ink-700">
                        <p>{{ __('hanbell.shipping.dispatch_note') }}</p>
                        <p>{{ __('hanbell.shipping.nationwide') }}. {{ __('hanbell.shipping.international') }}.</p>
                        <p>
                            {{ __('hanbell.shipping.flat_rate') }}:
                            {{ \App\Support\Money::format((int) config('hanbell.shipping.flat_minor', 200000)) }} —
                            {{ __('hanbell.shipping.free_over', ['amount' => \App\Support\Money::format((int) config('hanbell.shipping.free_threshold_minor', 5000000))]) }}.
                        </p>

                        <div class="pt-2">
                            <a href="{{ route('storefront.pages.show', ['slug' => 'returns-policy']) }}" wire:navigate class="text-sm font-semibold text-brand-700 underline underline-offset-2">
                                {{ __('hanbell.footer.returns') }}
                                <x-heroicon-m-arrow-right class="inline size-3.5" />
                            </a>
                        </div>
                    </div>

                    {{-- Reviews --}}
                    <div x-show="tab === 'reviews'" x-cloak role="tabpanel" id="reviews">
                        @php $reviews = $product->approvedReviews()->with('user')->limit(10)->get(); @endphp

                        @if ($reviews->isEmpty())
                            <p class="text-sm text-ink-500">{{ __('hanbell.common.no_items') }}</p>
                        @else
                            <ul class="space-y-5">
                                @foreach ($reviews as $review)
                                    <li class="border-b border-ink-100 pb-5 last:border-0">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex items-center gap-3">
                                                <span class="flex size-9 items-center justify-center rounded-full bg-ink-100 text-xs font-bold text-ink-600">
                                                    {{ $review->user?->initials() ?? '?' }}
                                                </span>
                                                <div>
                                                    <p class="text-sm font-semibold text-ink-900">
                                                        {{ $review->user?->name ?? __('hanbell.common.not_available') }}
                                                    </p>
                                                    @if ($review->is_verified_purchase)
                                                        <p class="flex items-center gap-1 text-[11px] font-medium text-brand-700">
                                                            <x-heroicon-s-check-badge class="size-3.5" />
                                                            {{ __('hanbell.product.sold_by') }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>

                                            <x-ui.stars :rating="$review->rating" :show-count="false" size="sm" />
                                        </div>

                                        @if ($review->title)
                                            <p class="mt-3 text-sm font-semibold text-ink-900">{{ $review->title }}</p>
                                        @endif

                                        @if ($review->body)
                                            <p class="mt-1.5 text-sm leading-relaxed text-ink-600">{{ $review->body }}</p>
                                        @endif

                                        <p class="mt-2 text-[11px] text-ink-400">{{ $review->created_at->diffForHumans() }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Vendor card --}}
            <aside class="lg:col-span-4">
                @if ($product->vendor)
                    <x-ui.card>
                        <div class="flex items-center gap-3">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-base font-extrabold text-white">
                                {{ \Illuminate\Support\Str::substr($product->vendor->name, 0, 2) }}
                            </span>

                            <div class="min-w-0">
                                <p class="clamp-1 text-sm font-bold text-ink-950">{{ $product->vendor->name }}</p>
                                <p class="text-xs text-ink-500">{{ $product->vendor->location() }}</p>
                            </div>
                        </div>

                        @if ($product->vendor->description)
                            <p class="mt-3 clamp-3 text-xs leading-relaxed text-ink-600">
                                {{ $product->vendor->description }}
                            </p>
                        @endif

                        <x-ui.button :href="$product->vendor->publicUrl()" variant="outline" block class="mt-4">
                            {{ __('hanbell.product.visit_store') }}
                        </x-ui.button>
                    </x-ui.card>
                @endif
            </aside>
        </div>

        {{-- Below-details banner --}}
        <div class="mt-10">
            <x-ads.slot :placement="AdPlacementKey::ProductBelowDetails" :limit="1" />
        </div>

        {{-- ============================================================
             Related rails
             ============================================================ --}}
        @if ($related->isNotEmpty())
            <x-ui.section :title="__('hanbell.product.related')" spacing="sm" class="!px-0">
                <div class="hb-rail -mx-1 px-1 pb-2">
                    @foreach ($related as $item)
                        <div class="w-[46%] shrink-0 sm:w-[31%] lg:w-[19.2%]">
                            <x-product.card :product="$item" />
                        </div>
                    @endforeach
                </div>
            </x-ui.section>
        @endif

        @if ($moreFromVendor->isNotEmpty())
            <x-ui.section
                :title="__('hanbell.product.more_from', ['brand' => $product->vendor?->name ?? ''])"
                spacing="sm"
                class="!px-0"
            >
                <div class="hb-rail -mx-1 px-1 pb-2">
                    @foreach ($moreFromVendor as $item)
                        <div class="w-[46%] shrink-0 sm:w-[31%] lg:w-[19.2%]">
                            <x-product.card :product="$item" />
                        </div>
                    @endforeach
                </div>
            </x-ui.section>
        @endif
    </div>
</div>
