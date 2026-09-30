@php
    /**
     * The site logo.
     *
     * `variant` picks the real brand asset: the green/yellow lockup for light
     * backgrounds, the yellow knockout for dark ones. Both are the genuine
     * logo files — never a redrawn approximation.
     */
    $variant = $variant ?? 'default';
    $src = $variant === 'light' ? 'images/logo-wt.svg' : 'images/logo.svg';
    $height = $height ?? 'h-8';
@endphp

<a
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 shrink-0']) }}
    href="{{ route('storefront.home') }}"
    aria-label="{{ config('hanbell.name') }} — home"
>
    <img
        src="{{ asset($src) }}"
        alt="{{ config('hanbell.name') }}"
        class="{{ $height }} w-auto"
        width="940"
        height="188"
        loading="eager"
        decoding="async"
    >
</a>
