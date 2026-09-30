@php
    /**
     * Icon-only brand mark, cropped from the real logo file.
     *
     * The logo is a 940×188 wordmark whose bell device sits at the left. Rather
     * than redraw the icon (which would be an approximation of the brand), this
     * crops the genuine asset with object-fit and a negative offset.
     */
    $size = $size ?? 'size-9';
    $variant = $variant ?? 'default';
@endphp

<span
    {{ $attributes->merge(['class' => "relative inline-flex $size shrink-0 items-center justify-center overflow-hidden rounded-lg bg-ink-950"]) }}
    role="img"
    aria-label="{{ config('hanbell.name') }}"
>
    <img
        src="{{ asset('images/logo-wt.svg') }}"
        alt=""
        aria-hidden="true"
        class="absolute h-[125%] w-auto max-w-none -translate-x-[7%] object-cover"
        loading="eager"
        decoding="async"
    >
</span>
