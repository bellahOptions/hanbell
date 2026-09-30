@php
    $links = [
        ['route' => 'storefront.account.dashboard', 'icon' => 'squares-2x2', 'label' => __('hanbell.account.dashboard'), 'match' => 'storefront.account.dashboard'],
        ['route' => 'storefront.account.orders', 'icon' => 'cube', 'label' => __('hanbell.account.orders'), 'match' => 'storefront.account.orders'],
        ['route' => 'storefront.account.wishlist', 'icon' => 'heart', 'label' => __('hanbell.nav.wishlist'), 'match' => 'storefront.account.wishlist'],
        ['route' => 'storefront.account.addresses', 'icon' => 'map-pin', 'label' => __('hanbell.account.addresses'), 'match' => 'storefront.account.addresses'],
        ['route' => 'storefront.account.security', 'icon' => 'shield-check', 'label' => __('hanbell.account.security'), 'match' => 'storefront.account.security'],
    ];
@endphp

<nav class="mt-6 flex gap-1 overflow-x-auto border-b border-ink-200 pb-px no-scrollbar" aria-label="{{ __('hanbell.account.title') }}">
    @foreach ($links as $link)
        @php $active = request()->routeIs($link['match']); @endphp

        <a
            href="{{ route($link['route']) }}"
            wire:navigate
            @class([
                'flex shrink-0 items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition',
                'border-brand-600 text-brand-700' => $active,
                'border-transparent text-ink-500 hover:border-ink-300 hover:text-ink-800' => ! $active,
            ])
            @if ($active) aria-current="page" @endif
        >
            <x-dynamic-component :component="'heroicon-o-'.$link['icon']" class="size-4" />
            {{ $link['label'] }}
        </a>
    @endforeach
</nav>
