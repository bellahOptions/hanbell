@props([
    'label',
    'value',
    'hint' => null,
    'delta' => null,
    'icon' => null,
    'tone' => 'neutral',
])

@php
    /**
     * Admin KPI tile.
     *
     * `delta` is null when there is no comparable previous period. That is
     * deliberate: rendering "+100%" against a zero baseline is noise, so the
     * badge is simply omitted.
     */
    $tones = [
        'brand' => 'bg-brand-50 text-brand-600',
        'info' => 'bg-info-50 text-info-600',
        'accent' => 'bg-accent-100 text-accent-800',
        'warning' => 'bg-warning-50 text-warning-600',
        'danger' => 'bg-danger-50 text-danger-600',
        'neutral' => 'bg-ink-100 text-ink-500',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-ink-200 bg-white p-4']) }}>
    <div class="flex items-start justify-between gap-2">
        @if ($icon)
            <span class="flex size-9 items-center justify-center rounded-lg {{ $tones[$tone] ?? $tones['neutral'] }}">
                <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-4.5" />
            </span>
        @endif

        @if (is_array($delta))
            <span @class([
                'flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[10px] font-bold',
                'bg-brand-50 text-brand-700' => ($delta['direction'] ?? '') === 'up',
                'bg-danger-50 text-danger-600' => ($delta['direction'] ?? '') === 'down',
                'bg-ink-100 text-ink-500' => ($delta['direction'] ?? '') === 'flat',
            ])>
                @if (($delta['direction'] ?? '') === 'up')
                    <x-heroicon-m-arrow-trending-up class="size-3" />
                @elseif (($delta['direction'] ?? '') === 'down')
                    <x-heroicon-m-arrow-trending-down class="size-3" />
                @endif
                {{ abs($delta['value'] ?? 0) }}%
            </span>
        @endif
    </div>

    <p class="mt-3 text-2xl font-extrabold tabular-nums tracking-tight text-ink-950">{{ $value }}</p>
    <p class="clamp-1 mt-0.5 text-xs font-medium text-ink-500">{{ $label }}</p>

    @if ($hint)
        <p class="clamp-1 mt-1 text-[11px] text-ink-400">{{ $hint }}</p>
    @endif
</div>
