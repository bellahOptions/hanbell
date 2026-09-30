@php
    use App\Enums\AdPlacementKey;
@endphp

<div class="hb-container py-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink-950 sm:text-3xl">
        {{ __('hanbell.account.greeting', ['name' => $user->firstName()]) }}
    </h1>

    @include('partials.account-nav')

    {{-- Summary tiles --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['label' => __('hanbell.order.orders'), 'value' => $orderCount, 'icon' => 'cube', 'href' => route('storefront.account.orders')],
            ['label' => __('hanbell.account.saved_addresses'), 'value' => $addressCount, 'icon' => 'map-pin', 'href' => route('storefront.account.addresses')],
            ['label' => __('hanbell.nav.wishlist'), 'value' => $wishlistCount, 'icon' => 'heart', 'href' => route('storefront.account.wishlist')],
            ['label' => __('hanbell.order.total'), 'value' => \App\Support\Money::compact($spentMinor), 'icon' => 'banknotes', 'href' => route('storefront.account.orders')],
        ] as $tile)
            <a href="{{ $tile['href'] }}" wire:navigate class="flex items-center gap-3 rounded-xl border border-ink-200 bg-white p-4 transition hover:border-brand-600/40 hover:shadow-card">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                    <x-dynamic-component :component="'heroicon-o-'.$tile['icon']" class="size-5" />
                </span>
                <span class="min-w-0">
                    <span class="block text-xl font-extrabold tabular-nums text-ink-950">{{ $tile['value'] }}</span>
                    <span class="clamp-1 block text-xs font-medium text-ink-500">{{ $tile['label'] }}</span>
                </span>
            </a>
        @endforeach
    </div>

    {{-- Recent orders --}}
    <div class="mt-6 grid gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-admin.panel :title="__('hanbell.order.orders')" padding="none">
                <x-slot:actions>
                    <a href="{{ route('storefront.account.orders') }}" wire:navigate class="text-xs font-semibold text-brand-700 hover:text-brand-800">
                        {{ __('hanbell.common.view_all') }}
                    </a>
                </x-slot:actions>

                @if ($recentOrders->isEmpty())
                    <x-ui.empty-state
                        icon="cube"
                        :title="__('hanbell.order.no_orders')"
                        :message="__('hanbell.order.no_orders_hint')"
                        :action-label="__('hanbell.cart.start_shopping')"
                        :action-href="route('storefront.shop')"
                    />
                @else
                    <ul class="divide-y divide-ink-100">
                        @foreach ($recentOrders as $order)
                            <li>
                                <a href="{{ $order->publicUrl() }}" wire:navigate class="flex items-center gap-4 p-5 transition hover:bg-ink-50/60">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-ink-100 text-ink-500">
                                        <x-heroicon-o-cube class="size-5" />
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-ink-900">{{ $order->number }}</span>
                                        <span class="block text-xs text-ink-500">
                                            {{ $order->created_at->format('j M Y') }} · {{ trans_choice('hanbell.shop.results_count', $order->itemCount(), ['count' => $order->itemCount()]) }}
                                        </span>
                                    </span>

                                    <span class="shrink-0 text-right">
                                        <span class="block text-sm font-bold tabular-nums text-ink-900">{{ $order->formattedTotal() }}</span>
                                        <x-ui.badge :class="$order->status->badgeClasses()" size="xs">{{ $order->status->label() }}</x-ui.badge>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.panel>
        </div>

        <div class="space-y-4">
            <x-ads.slot :placement="AdPlacementKey::AccountSidebar" :limit="1" />

            <x-ui.card>
                <h2 class="text-sm font-bold text-ink-950">{{ __('hanbell.account.security') }}</h2>
                <p class="mt-1.5 text-xs leading-relaxed text-ink-500">
                    @if ($user->hasTwoFactorEnabled())
                        {{ __('hanbell.account.two_factor_enabled') }}
                    @else
                        {{ __('hanbell.account.two_factor_disabled_hint') }}
                    @endif
                </p>

                <x-ui.button :href="route('storefront.account.security')" variant="outline" size="sm" block class="mt-4">
                    {{ $user->hasTwoFactorEnabled() ? __('hanbell.common.settings') : __('hanbell.account.enable_two_factor') }}
                </x-ui.button>
            </x-ui.card>
        </div>
    </div>
</div>
