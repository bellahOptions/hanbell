@props([
    'variant' => 'elevated',
    'padding' => 'md',
    'as' => 'div',
])

@php
    /**
     * Surface card. The shadcn-style elevation set, adapted to the brand's
     * warm neutral scale.
     */
    $variants = [
        'elevated' => 'bg-white border border-ink-200 shadow-card',
        'flat' => 'bg-white border border-ink-200',
        'muted' => 'bg-ink-50 border border-ink-200',
        'dark' => 'bg-ink-950 border border-ink-900 text-white',
        'brand' => 'bg-brand-600 border border-brand-700 text-white',
        'ghost' => 'bg-transparent',
    ];

    $paddings = [
        'none' => '',
        'sm' => 'p-4',
        'md' => 'p-5 sm:p-6',
        'lg' => 'p-6 sm:p-8',
    ];

    $classes = 'rounded-2xl '
        .($variants[$variant] ?? $variants['elevated']).' '
        .($paddings[$padding] ?? $paddings['md']);
@endphp

<{{ $as }} {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $as }}>
