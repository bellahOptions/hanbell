<div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.orders') }}</h1>
            <p class="mt-1 text-sm text-ink-500">
                {{ $orders->total() }} order(s) · {{ \App\Support\Money::format($revenueMinor) }} paid in this view
            </p>
        </div>
    </div>

    <div class="flex flex-wrap gap-1.5">
        <button type="button" wire:click="$set('status', '')"
            @class(['rounded-lg px-3 py-1.5 text-xs font-semibold transition', 'bg-ink-950 text-white' => $status === '', 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100' => $status !== ''])>
            {{ __('hanbell.common.all') }} ({{ $counts['all'] ?? 0 }})
        </button>

        @foreach (\App\Enums\OrderStatus::cases() as $case)
            @if (($counts[$case->value] ?? 0) > 0)
                <button type="button" wire:click="$set('status', '{{ $case->value }}')"
                    @class(['rounded-lg px-3 py-1.5 text-xs font-semibold transition', 'bg-ink-950 text-white' => $status === $case->value, 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100' => $status !== $case->value])>
                    {{ $case->label() }} ({{ $counts[$case->value] }})
                </button>
            @endif
        @endforeach
    </div>

    <x-admin.panel padding="none">
        <div class="border-b border-ink-100 p-4">
            <x-admin.toolbar search="search" searchPlaceholder="Order number, name or email…">
                <select wire:model.live="payment" class="h-10 rounded-lg border border-ink-300 bg-white px-3 text-sm">
                    <option value="">{{ __('hanbell.common.all') }}</option>
                    <option value="paid">{{ __('hanbell.order.statuses.paid') }}</option>
                    <option value="unpaid">{{ __('hanbell.order.statuses.pending') }}</option>
                </select>
            </x-admin.toolbar>
        </div>

        @if ($orders->isEmpty())
            <x-ui.empty-state icon="shopping-cart" :title="__('hanbell.order.no_orders')" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-ink-200 bg-ink-50/60">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-500">
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.order.number') }}</th>
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.admin.customers') }}</th>
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.common.date') }}</th>
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.common.status') }}</th>
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.order.payment_status') }}</th>
                            <th scope="col" class="px-4 py-2.5 text-right">{{ __('hanbell.common.total') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100">
                        @foreach ($orders as $order)
                            <tr class="transition hover:bg-ink-50/60">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-semibold text-ink-900 transition hover:text-brand-700">
                                        {{ $order->number }}
                                    </a>
                                    <p class="text-xs text-ink-500">{{ $order->items_count }} item(s)</p>
                                </td>

                                <td class="px-4 py-3">
                                    <p class="clamp-1 max-w-40 text-sm text-ink-800">{{ $order->customer_name }}</p>
                                    <p class="clamp-1 max-w-40 text-xs text-ink-500">{{ $order->email }}</p>
                                </td>

                                <td class="px-4 py-3 text-xs text-ink-500">{{ $order->created_at->format('j M Y') }}</td>

                                <td class="px-4 py-3">
                                    <x-ui.badge :class="$order->status->badgeClasses()" size="sm">{{ $order->status->label() }}</x-ui.badge>
                                </td>

                                <td class="px-4 py-3">
                                    <x-ui.badge :class="$order->payment_status->badgeClasses()" size="sm">{{ $order->payment_status->label() }}</x-ui.badge>
                                </td>

                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-ink-900">
                                    {{ $order->formattedTotal() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 p-4">{{ $orders->links() }}</div>
        @endif
    </x-admin.panel>
</div>
