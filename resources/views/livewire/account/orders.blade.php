@php
    use App\Enums\OrderStatus;
@endphp

<div class="hb-container py-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink-950 sm:text-3xl">{{ __('hanbell.account.orders') }}</h1>

    @include('partials.account-nav')

    {{-- Status filter --}}
    <div class="mt-6 flex flex-wrap gap-1.5">
        <button
            type="button"
            wire:click="$set('filter', 'all')"
            @class([
                'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                'bg-ink-950 text-white' => $filter === 'all',
                'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100' => $filter !== 'all',
            ])
        >
            {{ __('hanbell.common.all') }}
        </button>

        @foreach (OrderStatus::cases() as $status)
            <button
                type="button"
                wire:click="$set('filter', '{{ $status->value }}')"
                @class([
                    'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                    'bg-ink-950 text-white' => $filter === $status->value,
                    'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100' => $filter !== $status->value,
                ])
            >
                {{ $status->label() }}
            </button>
        @endforeach
    </div>

    <div class="mt-5">
        @if ($orders->isEmpty())
            <x-ui.card padding="none">
                <x-ui.empty-state
                    icon="cube"
                    :title="__('hanbell.order.no_orders')"
                    :message="__('hanbell.order.no_orders_hint')"
                    :action-label="__('hanbell.cart.start_shopping')"
                    :action-href="route('storefront.shop')"
                />
            </x-ui.card>
        @else
            <div class="space-y-4">
                @foreach ($orders as $order)
                    <x-ui.card padding="none">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 bg-ink-50/60 px-5 py-3.5">
                            <div class="flex flex-wrap items-center gap-x-5 gap-y-1">
                                <span>
                                    <span class="block text-[10px] font-semibold uppercase tracking-wider text-ink-400">{{ __('hanbell.order.number') }}</span>
                                    <span class="text-sm font-bold text-ink-900">{{ $order->number }}</span>
                                </span>

                                <span>
                                    <span class="block text-[10px] font-semibold uppercase tracking-wider text-ink-400">{{ __('hanbell.order.placed_on') }}</span>
                                    <span class="text-sm text-ink-600">{{ $order->created_at->format('j M Y') }}</span>
                                </span>

                                <span>
                                    <span class="block text-[10px] font-semibold uppercase tracking-wider text-ink-400">{{ __('hanbell.common.total') }}</span>
                                    <span class="text-sm font-bold tabular-nums text-ink-900">{{ $order->formattedTotal() }}</span>
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <x-ui.badge :class="$order->status->badgeClasses()" size="sm">{{ $order->status->label() }}</x-ui.badge>

                                <x-ui.button :href="$order->publicUrl()" variant="outline" size="sm">
                                    {{ __('hanbell.common.details') }}
                                </x-ui.button>
                            </div>
                        </div>

                        @if ($order->items->isNotEmpty())
                            <div class="flex flex-wrap items-center gap-3 p-5">
                                @foreach ($order->items->take(4) as $item)
                                    <div class="hb-frame size-14 rounded-lg" title="{{ $item->product_name }}">
                                        <img src="{{ $item->imageUrl() }}" alt="{{ $item->product_name }}" loading="lazy" class="object-cover">
                                    </div>
                                @endforeach

                                @if ($order->items->count() > 4)
                                    <span class="text-xs font-medium text-ink-500">+{{ $order->items->count() - 4 }}</span>
                                @endif

                                @if ($order->tracking_number ?? false)
                                    <span class="ml-auto text-xs text-ink-500">
                                        {{ __('hanbell.shipping.tracking') }}:
                                        <span class="font-mono font-semibold text-ink-800">{{ $order->tracking_number }}</span>
                                    </span>
                                @endif
                            </div>
                        @endif
                    </x-ui.card>
                @endforeach
            </div>

            <div class="mt-8">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
