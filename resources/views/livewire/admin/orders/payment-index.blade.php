<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.payments') }}</h1>
        <p class="mt-1 text-sm text-ink-500">{{ $payments->total() }} payment record(s)</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-admin.stat :label="__('hanbell.admin.revenue')" :value="\App\Support\Money::compact($collectedMinor)" icon="banknotes" tone="brand" />
        <x-admin.stat label="Refunded" :value="\App\Support\Money::compact($refundedMinor)" icon="arrow-uturn-left" tone="warning" />
        <x-admin.stat label="Failed attempts" :value="number_format($failedCount)" icon="x-circle" tone="danger" />
    </div>

    <x-admin.panel padding="none">
        <div class="border-b border-ink-100 p-4">
            <x-admin.toolbar search="search" searchPlaceholder="Reference…">
                <select wire:model.live="provider" class="h-10 rounded-lg border border-ink-300 bg-white px-3 text-sm">
                    <option value="">{{ __('hanbell.common.all') }}</option>
                    @foreach (\App\Enums\PaymentProvider::cases() as $p)
                        <option value="{{ $p->value }}">{{ $p->label() }}</option>
                    @endforeach
                </select>

                <select wire:model.live="status" class="h-10 rounded-lg border border-ink-300 bg-white px-3 text-sm">
                    <option value="">{{ __('hanbell.common.all') }}</option>
                    @foreach (\App\Enums\PaymentStatus::cases() as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
            </x-admin.toolbar>
        </div>

        @if ($payments->isEmpty())
            <x-ui.empty-state icon="credit-card" :title="__('hanbell.common.no_items')" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-ink-200 bg-ink-50/60">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-500">
                            <th scope="col" class="px-4 py-2.5">Reference</th>
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.order.order') }}</th>
                            <th scope="col" class="px-4 py-2.5">Provider</th>
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.common.status') }}</th>
                            <th scope="col" class="px-4 py-2.5">Channel</th>
                            <th scope="col" class="px-4 py-2.5 text-right">{{ __('hanbell.common.total') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100">
                        @foreach ($payments as $payment)
                            <tr class="transition hover:bg-ink-50/60">
                                <td class="px-4 py-3">
                                    <p class="font-mono text-xs font-semibold text-ink-900">{{ $payment->reference }}</p>
                                    @if ($payment->provider_reference)
                                        <p class="clamp-1 max-w-40 font-mono text-[11px] text-ink-400">{{ $payment->provider_reference }}</p>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    @if ($payment->order)
                                        <a href="{{ route('admin.orders.show', $payment->order) }}" wire:navigate class="text-sm font-medium text-ink-800 transition hover:text-brand-700">
                                            {{ $payment->order->number }}
                                        </a>
                                        <p class="clamp-1 max-w-40 text-xs text-ink-500">{{ $payment->order->email }}</p>
                                    @else
                                        <span class="text-ink-300">—</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-sm text-ink-700">{{ $payment->providerLabel() }}</td>

                                <td class="px-4 py-3">
                                    <x-ui.badge :class="$payment->status->badgeClasses()" size="sm">{{ $payment->status->label() }}</x-ui.badge>

                                    @if ($payment->failure_reason)
                                        <p class="mt-1 clamp-2 max-w-48 text-[11px] text-danger-600">{{ $payment->failure_reason }}</p>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-xs text-ink-500">{{ $payment->channel ?: '—' }}</td>

                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-ink-900">
                                    {{ $payment->formattedAmount() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 p-4">{{ $payments->links() }}</div>
        @endif
    </x-admin.panel>
</div>
