@props([
    'rating' => 0,
    'count' => null,
    'size' => 'sm',
    'showCount' => true,
])

@php
    /**
     * Star rating.
     *
     * blade-heroicons ships only full outline/solid stars — there is no
     * half-star icon. A half rating is therefore drawn by clipping a solid star
     * over an outline one with an inline width, rather than referencing a
     * component that does not exist (which would compile but throw at render).
     */
    $rating = max(0, min(5, (float) $rating));

    $sizes = [
        'xs' => 'size-3',
        'sm' => 'size-3.5',
        'md' => 'size-4',
        'lg' => 'size-5',
    ];

    $starSize = $sizes[$size] ?? $sizes['sm'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5']) }}>
    <span class="inline-flex items-center gap-px" role="img" aria-label="{{ number_format($rating, 1) }} out of 5 stars">
        @for ($i = 1; $i <= 5; $i++)
            @php
                $fill = max(0, min(1, $rating - ($i - 1)));
            @endphp

            <span class="relative inline-block {{ $starSize }}">
                <x-heroicon-o-star class="absolute inset-0 {{ $starSize }} text-ink-300" />

                @if ($fill > 0)
                    <span class="absolute inset-0 overflow-hidden" style="width: {{ $fill * 100 }}%">
                        <x-heroicon-s-star class="{{ $starSize }} text-accent-500" />
                    </span>
                @endif
            </span>
        @endfor
    </span>

    @if ($showCount && $count !== null)
        <span class="text-xs font-medium text-ink-500">
            ({{ number_format($count) }})
        </span>
    @endif
</span>
