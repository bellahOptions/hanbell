@props([
    'placement',
    'fallback' => false,
    'limit' => null,
    'class' => null,
])

@php
    use App\Enums\AdPlacementKey;
    use App\Services\Advertising\AdServer;

    /**
     * Renders one ad slot.
     *
     * The slot is a closed enum member with exactly one real call site in the
     * codebase, which is why a typo here is a visible error rather than a slot
     * that silently renders nothing forever.
     *
     * When nothing is booked the component renders nothing at all — unless the
     * caller passes `fallback`, which is how layouts that must keep their shape
     * (the homepage hero) stay intact.
     */
    $key = $placement instanceof AdPlacementKey ? $placement : AdPlacementKey::tryFrom((string) $placement);

    $creatives = $key
        ? app(AdServer::class)->fill($key, $limit)
        : collect();
@endphp

@if ($creatives->isNotEmpty())
    <div {{ $attributes->merge(['class' => $class ?? '']) }} data-ad-slot="{{ $key->value }}">
        @if ($key->isRail() && $creatives->count() > 1)
            <div class="hb-rail">
                @foreach ($creatives as $creative)
                    <x-ads.tile :creative="$creative" :placement="$key" class="w-[85%] shrink-0 sm:w-[45%] lg:w-auto lg:flex-1" />
                @endforeach
            </div>
        @else
            <div class="{{ $creatives->count() > 1 ? 'grid gap-4 sm:grid-cols-2' : '' }}">
                @foreach ($creatives as $creative)
                    <x-ads.tile :creative="$creative" :placement="$key" />
                @endforeach
            </div>
        @endif
    </div>
@elseif ($fallback)
    {{ $slot }}
@endif
