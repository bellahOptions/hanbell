@props([
    'product',
    'eager' => false,
])

@php
    /**
     * Product tile. The single most repeated element on the site, so it carries
     * the whole visual language: a tall image frame that scales on hover, a
     * discount ribbon in the brand yellow, and the maker's name given equal
     * weight to the price — because "who made this" is the point of HanbellShop.
     */
    $image = $product->primaryImage();
    $discount = $product->discountPercent();
    $inStock = $product->isInStock();
    $lowStock = $product->isLowStock();
    $url = $product->publicUrl();
@endphp

<article {{ $attributes->merge(['class' => 'group relative flex flex-col']) }}>
    <a href="{{ $url }}" wire:navigate class="block focus-visible:outline-none" aria-label="{{ $product->name }}">
        <div class="hb-frame aspect-[3/4] w-full rounded-xl ring-1 ring-ink-200/70">
            <img
                src="{{ $image?->url() ?? \App\Models\Product::placeholderImageUrl() }}"
                alt="{{ $image?->alt() ?? $product->name }}"
                loading="{{ $eager ? 'eager' : 'lazy' }}"
                decoding="async"
                width="600"
                height="800"
                class="transition-opacity duration-500"
            >

            {{-- Flags stack top-left; a discount beats a "new" flag for attention. --}}
            <div class="pointer-events-none absolute left-2.5 top-2.5 z-10 flex flex-col items-start gap-1.5">
                @if ($discount)
                    <span class="rounded-md bg-accent-300 px-2 py-1 text-[11px] font-extrabold tracking-tight text-ink-950 shadow-sm">
                        -{{ $discount }}%
                    </span>
                @elseif ($product->is_new_arrival)
                    <span class="rounded-md bg-ink-950 px-2 py-1 text-[11px] font-bold uppercase tracking-wide text-white shadow-sm">
                        {{ __('hanbell.product.new_badge') }}
                    </span>
                @endif

                @if ($product->is_trending && ! $discount)
                    <span class="rounded-md bg-brand-600 px-2 py-1 text-[11px] font-bold uppercase tracking-wide text-white shadow-sm">
                        {{ __('hanbell.product.trending_badge') }}
                    </span>
                @endif
            </div>

            {{-- Sold-out veil: the tile stays clickable so the piece can still be viewed. --}}
            @unless ($inStock)
                <div class="absolute inset-0 z-10 flex items-center justify-center bg-white/72 backdrop-blur-[2px]">
                    <span class="rounded-full bg-ink-950 px-3.5 py-1.5 text-xs font-bold uppercase tracking-wide text-white">
                        {{ __('hanbell.product.out_of_stock') }}
                    </span>
                </div>
            @endunless
        </div>
    </a>

    {{-- Wishlist toggle sits outside the <a> so it does not trigger navigation. --}}
    @auth
        @unless (auth()->user()->isAdmin())
            <livewire:storefront.toggle-wishlist :product-id="$product->id" :key="'wish-'.$product->id" />
        @endunless
    @else
        <livewire:storefront.toggle-wishlist :product-id="$product->id" :key="'wish-'.$product->id" />
    @endauth

    <div class="mt-3 flex flex-1 flex-col">
        @if ($product->vendor)
            <p class="mb-1 truncate text-[11px] font-bold uppercase tracking-wider text-ink-400">
                {{ $product->vendor->name }}
            </p>
        @endif

        <h3 class="clamp-2 text-sm font-semibold leading-snug text-ink-900">
            <a href="{{ $url }}" wire:navigate class="transition hover:text-brand-700">
                {{ $product->name }}
            </a>
        </h3>

        <div class="mt-2 flex flex-wrap items-baseline gap-x-2 gap-y-1">
            <x-ui.price
                :amount="$product->priceFor()"
                :compare-at="$product->compareAtPriceFor()"
                :currency="$product->currency"
                size="sm"
            />
        </div>

        <div class="mt-1.5 flex items-center gap-2">
            @if ($product->rating_count > 0)
                <x-ui.stars :rating="$product->rating_average" :count="$product->rating_count" size="xs" />
            @endif

            @if ($lowStock && $inStock)
                <span class="text-[11px] font-semibold text-warning-600">
                    {{ __('hanbell.product.low_stock', ['count' => $product->availableQuantity()]) }}
                </span>
            @endif
        </div>
    </div>
</article>
