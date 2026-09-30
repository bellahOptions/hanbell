<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.orders') }}</h1>
        <p class="mt-1 text-sm text-ink-500">{{ $orders->total() }} paid order(s)</p>
    </div>

    <div class="flex flex-wrap gap-1.5">
        @foreach (['all' => __('hanbell.common.all'), 'paid' => __('hanbell.order.statuses.paid'), 'shipped' => __('hanbell.order.statuses.shipped'), 'delivered' => __('hanbell.order.statuses.delivered')] as $value => $label)
            <button
                type="button"
                wire:click="$set('filter', '{{ $value }}')"
                @class([
                    'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                    'bg-ink-950 text-white' => $filter === $value,
                    'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100' => $filter !== $value,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <x-admin.panel padding="none">
        @if ($orders->isEmpty())
            <x-ui.empty-state icon="shopping-cart" :title="__('hanbell.order.no_orders')" />
        @else
            <ul class="divide-y divide-ink-100">
                @foreach ($orders as $vendorOrder)
                    <li class="p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-ink-900">{{ $vendorOrder->number }}</p>
                                <p class="mt-0.5 text-xs text-ink-500">
                                    {{ $vendorOrder->order?->customer_name }} ·
                                    {{ $vendorOrder->created_at->format('j M Y') }}
                                </p>

                                <ul class="mt-3 space-y-1">
                                    @foreach ($vendorOrder->items as $item)
                                        <li class="text-xs text-ink-600">
                                            {{ $item->quantity }} × {{ $item->product_name }}
                                            @if ($item->variant_label)
                                                <span class="text-ink-400">({{ $item->variant_label }})</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="shrink-0 text-right">
                                <p class="text-sm font-bold tabular-nums text-ink-900">{{ $vendorOrder->formattedPayout() }}</p>
                                <p class="mt-0.5 text-[11px] text-ink-500">
                                    payout · {{ $vendorOrder->commission_percent }}% commission
                                </p>

                                <div class="mt-2">
                                    <x-ui.badge :class="$vendorOrder->status->badgeClasses()" size="sm">
                                        {{ $vendorOrder->status->label() }}
                                    </x-ui.badge>
                                </div>

                                <div class="mt-3 flex justify-end gap-2">
                                    @if ($vendorOrder->status->value === 'paid')
                                        <x-ui.button wire:click="markShipped({{ $vendorOrder->id }})" variant="outline" size="sm">
                                            {{ __('hanbell.order.statuses.shipped') }}
                                        </x-ui.button>
                                    @elseif ($vendorOrder->status->value === 'shipped')
                                        <x-ui.button wire:click="markDelivered({{ $vendorOrder->id }})" variant="primary" size="sm">
                                            {{ __('hanbell.order.statuses.delivered') }}
                                        </x-ui.button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="border-t border-ink-100 p-4">{{ $orders->links() }}</div>
        @endif
    </x-admin.panel>
</div>
