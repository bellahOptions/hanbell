@props([
    'amount' => 0,
    'currency' => null,
    'compareAt' => null,
    'size' => 'md',
    'compact' => false,
])

@php
    use App\Support\Money;

    /**
     * Price display. Uses tabular numerals so prices do not jitter as
     * quantities or filters change, and renders the "was" price struck through
     * when there is a discount.
     */
    $sizes = [
        'xs' => 'text-sm',
        'sm' => 'text-base',
        'md' => 'text-lg',
        'lg' => 'text-2xl',
        'xl' => 'text-3xl',
    ];

    $hasDiscount = $compareAt !== null && $compareAt > $amount;
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-baseline gap-2']) }}>
    <span class="tnum font-bold tracking-tight text-ink-950 {{ $sizes[$size] ?? $sizes['md'] }}">
        {{ $compact ? Money::compact($amount, $currency) : Money::format($amount, $currency) }}
    </span>

    @if ($hasDiscount)
        <span class="tnum text-sm font-medium text-ink-400 line-through">
            {{ Money::format($compareAt, $currency) }}
        </span>
    @endif
</span>
