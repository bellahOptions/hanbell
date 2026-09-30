@props([
    'variant' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    /**
     * Inline alert. Distinct from a toast: this one belongs in the page flow
     * (a form-level warning, a policy notice), whereas a toast is transient
     * feedback for an action the shopper just took.
     */
    $variants = [
        'info' => [
            'wrap' => 'border-info-500/25 bg-info-50 text-info-900',
            'icon' => 'text-info-600',
            'glyph' => 'information-circle',
        ],
        'success' => [
            'wrap' => 'border-brand-600/25 bg-brand-50 text-brand-900',
            'icon' => 'text-brand-600',
            'glyph' => 'check-circle',
        ],
        'warning' => [
            'wrap' => 'border-warning-500/30 bg-warning-50 text-warning-900',
            'icon' => 'text-warning-600',
            'glyph' => 'exclamation-triangle',
        ],
        'danger' => [
            'wrap' => 'border-danger-500/25 bg-danger-50 text-danger-900',
            'icon' => 'text-danger-600',
            'glyph' => 'x-circle',
        ],
        'neutral' => [
            'wrap' => 'border-ink-200 bg-ink-50 text-ink-800',
            'icon' => 'text-ink-500',
            'glyph' => 'information-circle',
        ],
    ];

    $style = $variants[$variant] ?? $variants['info'];
@endphp

<div
    {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-xl border p-4 '.$style['wrap']]) }}
    role="{{ $variant === 'danger' ? 'alert' : 'status' }}"
    @if ($dismissible) x-data="{ open: true }" x-show="open" x-transition @endif
>
    <x-dynamic-component :component="'heroicon-o-'.$style['glyph']" class="mt-0.5 size-5 shrink-0 {{ $style['icon'] }}" />

    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="text-sm font-semibold">{{ $title }}</p>
        @endif

        <div class="{{ $title ? 'mt-1 text-sm' : 'text-sm' }}">
            {{ $slot }}
        </div>
    </div>

    @if ($dismissible)
        <button
            type="button"
            class="-mr-1 -mt-1 shrink-0 rounded-md p-1 opacity-60 transition hover:bg-black/5 hover:opacity-100"
            @click="open = false"
            aria-label="{{ __('hanbell.toast.dismiss') }}"
        >
            <x-heroicon-m-x-mark class="size-4" />
        </button>
    @endif
</div>
