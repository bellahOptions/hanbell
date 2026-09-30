@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'block' => false,
    'icon' => null,
    'iconTrailing' => null,
    'loading' => null,
])

@php
    /**
     * Button.
     *
     * Variant and size lookups deliberately fall back rather than throwing: a
     * typo in a template should degrade to a working button, not a 500 on a
     * page a shopper is trying to reach.
     *
     * Note this is NOT implicitly full width — pass `block` when that is wanted.
     */
    $variants = [
        // The brand's primary action: Hanbell green.
        'primary' => 'bg-brand-600 text-white hover:bg-brand-700 active:bg-brand-800 shadow-sm focus-visible:outline-brand-600',
        // Hanbell yellow — reserved for the highest-intent actions on dark or
        // neutral surfaces, where it has the most contrast.
        'accent' => 'bg-accent-300 text-ink-950 hover:bg-accent-400 active:bg-accent-500 shadow-sm focus-visible:outline-accent-500',
        'secondary' => 'bg-ink-950 text-white hover:bg-ink-800 active:bg-ink-700 shadow-sm focus-visible:outline-ink-900',
        'outline' => 'border border-ink-300 bg-white text-ink-900 hover:bg-ink-50 hover:border-ink-400 focus-visible:outline-ink-900',
        'ghost' => 'text-ink-700 hover:bg-ink-100 hover:text-ink-900 focus-visible:outline-ink-900',
        'danger' => 'bg-danger-600 text-white hover:bg-danger-500 focus-visible:outline-danger-600',
        'link' => 'text-brand-700 underline-offset-4 hover:underline hover:text-brand-800 p-0 h-auto',
    ];

    $sizes = [
        'xs' => 'h-8 px-2.5 text-xs gap-1.5 rounded-md',
        'sm' => 'h-9 px-3.5 text-sm gap-1.5 rounded-lg',
        'md' => 'h-11 px-5 text-sm gap-2 rounded-lg',
        'lg' => 'h-12 px-6 text-base gap-2 rounded-xl',
        'xl' => 'h-14 px-8 text-base gap-2.5 rounded-xl',
        'icon' => 'size-10 p-0 rounded-lg',
        'icon-sm' => 'size-8 p-0 rounded-md',
    ];

    $base = 'inline-flex items-center justify-center font-semibold tracking-tight transition-all duration-150 '
        . 'disabled:pointer-events-none disabled:opacity-50 focus-visible:outline-2 focus-visible:outline-offset-2 '
        . 'whitespace-nowrap select-none';

    $classes = collect([
        $base,
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
        $block ? 'w-full' : '',
        $variant === 'link' ? 'font-medium' : '',
    ])->filter()->implode(' ');

    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href)
        href="{{ $href }}"
        @if (str_starts_with($href, url('/')) && ! str_contains($href, '#')) wire:navigate @endif
    @else
        type="{{ $type }}"
    @endif
    @if ($loading !== null) wire:loading.attr="disabled" wire:target="{{ $loading }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    @if ($icon)
        <x-dynamic-component :component="'heroicon-o-' . $icon" class="size-4 shrink-0" />
    @endif

    {{ $slot }}

    @if ($iconTrailing)
        <x-dynamic-component :component="'heroicon-o-' . $iconTrailing" class="size-4 shrink-0" />
    @endif
</{{ $tag }}>
