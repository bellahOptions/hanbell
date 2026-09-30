@props([
    'variant' => 'neutral',
    'size' => 'md',
    'dot' => false,
])

@php
    /**
     * Badge / pill. Used for order statuses, product flags and admin table
     * states. Variants fall back rather than throwing on an unknown key.
     */
    $variants = [
        'neutral' => 'bg-ink-100 text-ink-700 ring-ink-200',
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-600/25',
        'success' => 'bg-brand-50 text-brand-700 ring-brand-600/25',
        'accent' => 'bg-accent-100 text-accent-800 ring-accent-500/30',
        'warning' => 'bg-warning-50 text-warning-600 ring-warning-500/25',
        'danger' => 'bg-danger-50 text-danger-600 ring-danger-500/25',
        'info' => 'bg-info-50 text-info-600 ring-info-500/25',
        'dark' => 'bg-ink-950 text-white ring-ink-950',
        'outline' => 'bg-transparent text-ink-600 ring-ink-300',
    ];

    $sizes = [
        'xs' => 'px-1.5 py-0.5 text-[10px] gap-1',
        'sm' => 'px-2 py-0.5 text-xs gap-1',
        'md' => 'px-2.5 py-1 text-xs gap-1.5',
        'lg' => 'px-3 py-1.5 text-sm gap-1.5',
    ];

    $classes = 'inline-flex items-center rounded-full font-semibold ring-1 ring-inset '
        .($variants[$variant] ?? $variants['neutral']).' '
        .($sizes[$size] ?? $sizes['md']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if ($dot)
        <span class="size-1.5 rounded-full bg-current opacity-70" aria-hidden="true"></span>
    @endif

    {{ $slot }}
</span>
