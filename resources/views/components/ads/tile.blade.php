@props([
    'creative',
    'placement',
])

@php
    use App\Models\AdPlacement;

    /**
     * A single ad creative.
     *
     * Two deliberate behaviours:
     *
     *  - `data-ad-creative` is the hook the viewability observer watches. The
     *    impression is only sent once this element has been at least 50%
     *    visible for a full second, so a creative nobody scrolled to is never
     *    billed.
     *
     *  - The click goes through the internal tracking route, never straight to
     *    the advertiser's URL, so the destination can be re-validated before
     *    the redirect.
     */
    $placementModel = AdPlacement::where('key', $placement->value)->first();
    $image = $creative->imageUrl();
    $hasImage = $creative->hasImage();
    $bg = $creative->background_color ?: '#0b0b0a';
    $fg = $creative->text_color ?: '#ffffff';
@endphp

<div
    class="group relative overflow-hidden rounded-xl {{ $placement->aspectClass() }}"
    data-ad-creative="{{ $creative->urlToken() }}"
    data-ad-placement="{{ $placement->value }}"
    data-ad-max-impressions="{{ $creative->campaign?->max_impressions_per_session ?? 3 }}"
    data-ad-impression-url="{{ route('ads.impression') }}"
    style="background-color: {{ $bg }};"
>
    <a
        href="{{ $creative->trackingUrl($placementModel?->id) }}"
        rel="nofollow sponsored noopener"
        target="{{ $creative->destination_type === 'internal' ? '_self' : '_blank' }}"
        class="absolute inset-0 z-10"
        aria-label="{{ $creative->headline ?: $creative->name }}"
    >
        <span class="sr-only">{{ $creative->headline ?: $creative->name }}</span>
    </a>

    @if ($hasImage)
        <img
            src="{{ $image }}"
            alt="{{ $creative->headline ?: $creative->name }}"
            loading="lazy"
            decoding="async"
            class="absolute inset-0 size-full object-cover transition-transform duration-700 group-hover:scale-[1.03]"
        >
    @endif

    {{-- Copy overlay. Kept legible over any photograph with a scrim rather than
         relying on the image being dark. --}}
    @if ($creative->headline || $creative->cta_label)
        <div
            class="absolute inset-0 flex flex-col justify-end p-4 sm:p-6"
            style="background: linear-gradient(to top, rgb(0 0 0 / {{ $hasImage ? '0.78' : '0.15' }}), transparent 65%);"
        >
            @if ($creative->subheadline)
                <p class="hb-eyebrow mb-1.5" style="color: {{ $fg }}; opacity: 0.85;">
                    {{ $creative->subheadline }}
                </p>
            @endif

            @if ($creative->headline)
                <p class="text-lg font-bold leading-tight tracking-tight sm:text-2xl" style="color: {{ $fg }};">
                    {{ $creative->headline }}
                </p>
            @endif

            @if ($creative->cta_label)
                <span
                    class="mt-3 inline-flex w-fit items-center gap-1.5 rounded-lg bg-accent-300 px-3.5 py-2 text-xs font-bold text-ink-950 shadow-sm transition group-hover:bg-accent-400"
                >
                    {{ $creative->cta_label }}
                    <x-heroicon-m-arrow-right class="size-3.5" />
                </span>
            @endif
        </div>
    @endif

    {{-- Advertisers are told their placement is paid for; shoppers are told it
         is an ad. Required for honesty, and by most advertising standards. --}}
    <span class="absolute right-2 top-2 z-20 rounded bg-black/45 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-white/90 backdrop-blur-sm">
        Ad
    </span>
</div>
