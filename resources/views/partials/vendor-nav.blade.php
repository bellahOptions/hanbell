@php
    $vendor = auth()->user()->vendor;

    $links = [
        ['route' => 'vendor.dashboard', 'icon' => 'squares-2x2', 'label' => __('hanbell.admin.dashboard'), 'match' => 'vendor.dashboard'],
        ['route' => 'vendor.products.index', 'icon' => 'tag', 'label' => __('hanbell.admin.products'), 'match' => 'vendor.products.*'],
        ['route' => 'vendor.inventory.index', 'icon' => 'archive-box', 'label' => __('hanbell.admin.inventory'), 'match' => 'vendor.inventory.*'],
        ['route' => 'vendor.orders.index', 'icon' => 'shopping-cart', 'label' => __('hanbell.admin.orders'), 'match' => 'vendor.orders.*'],
        ['route' => 'vendor.profile', 'icon' => 'building-storefront', 'label' => __('hanbell.vendor.brand'), 'match' => 'vendor.profile'],
    ];
@endphp

<header class="border-b border-ink-200 bg-white">
    <div class="hb-container">
        <div class="flex h-16 items-center gap-4">
            <x-site.mark size="size-8" />

            <div class="min-w-0">
                <p class="clamp-1 text-sm font-bold text-ink-950">{{ $vendor?->name ?? __('hanbell.vendor.brand') }}</p>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-ink-400">
                    {{ __('hanbell.admin.admin_panel') }} · {{ __('hanbell.vendor.brand') }}
                </p>
            </div>

            @if ($vendor)
                <x-ui.badge :class="$vendor->status->badgeClasses()" size="sm" class="hidden sm:inline-flex">
                    {{ $vendor->status->label() }}
                </x-ui.badge>
            @endif

            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('storefront.home') }}" wire:navigate class="hidden rounded-lg px-3 py-2 text-xs font-medium text-ink-500 transition hover:bg-ink-100 sm:block">
                    {{ __('hanbell.admin.back_to_store') }}
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg px-3 py-2 text-xs font-medium text-ink-500 transition hover:bg-ink-100">
                        {{ __('hanbell.common.logout') }}
                    </button>
                </form>
            </div>
        </div>

        <nav class="flex gap-1 overflow-x-auto pb-2 no-scrollbar" aria-label="{{ __('hanbell.vendor.brand') }}">
            @foreach ($links as $link)
                @php $active = request()->routeIs($link['match']); @endphp

                <a
                    href="{{ route($link['route']) }}"
                    wire:navigate
                    @class([
                        'flex shrink-0 items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium transition',
                        'bg-ink-950 text-white' => $active,
                        'text-ink-600 hover:bg-ink-100 hover:text-ink-900' => ! $active,
                    ])
                    @if ($active) aria-current="page" @endif
                >
                    <x-dynamic-component :component="'heroicon-o-'.$link['icon']" class="size-4" />
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</header>
