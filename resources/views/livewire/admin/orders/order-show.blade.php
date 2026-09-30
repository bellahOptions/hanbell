<div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ $order->number }}</h1>
            <p class="mt-1 text-sm text-ink-500">
                {{ $order->created_at->format('j M Y, H:i') }} ·
                {{ trans_choice('hanbell.shop.results_count', $order->itemCount(), ['count' => $order->itemCount()]) }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-ui.badge :class="$order->status->badgeClasses()" size="lg">{{ $order->status->label() }}</x-ui.badge>
            <x-ui.badge :class="$order->payment_status->badgeClasses()" size="lg">{{ $order->payment_status->label() }}</x-ui.badge>
        </div>
    </div>

    {{-- Status transitions. Only the legal next states are offered —
         OrderStatus::canTransitionTo() refuses anything else. --}}
    @if ($nextStatuses->isNotEmpty())
        <x-admin.panel :title="__('hanbell.common.status')" description="Only permitted transitions are shown.">
            <div class="flex flex-wrap gap-2">
                @foreach ($nextStatuses as $status)
                    <x-ui.button
                        wire:click="updateStatus('{{ $status->value }}')"
                        wire:confirm="Move this order to “{{ $status->label() }}”?"
                        variant="{{ $status->value === 'cancelled' || $status->value === 'refunded' ? 'outline' : 'primary' }}"
                        size="sm"
                    >
                        {{ $status->label() }}
                    </x-ui.button>
                @endforeach
            </div>
        </x-admin.panel>
    @endif

    {{-- Documents. An administrator can pull a vendor statement for any brand in
         the order, which a vendor cannot. --}}
    <x-admin.panel title="Documents" description="Generated on demand as PDFs.">
        <x-order.documents :order="$order" />

        @if ($order->vendorOrders->isNotEmpty())
            <div class="mt-4 border-t border-ink-100 pt-4">
                <p class="mb-2.5 text-xs font-semibold uppercase tracking-wider text-ink-400">
                    Vendor statements
                </p>

                <div class="flex flex-wrap gap-2">
                    @foreach ($order->vendorOrders as $vendorOrder)
                        <x-ui.button
                            :href="route('documents.vendor-statement', [
                                'order' => $order->urlToken(),
                                'vendorOrder' => $vendorOrder->id,
                            ])"
                            variant="outline"
                            size="sm"
                            target="_blank"
                            rel="noopener"
                        >
                            <x-heroicon-o-building-storefront class="size-4" />
                            {{ $vendorOrder->vendor?->name ?? $vendorOrder->number }}
                        </x-ui.button>
                    @endforeach
                </div>
            </div>
        @endif
    </x-admin.panel>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            @foreach ($order->vendorOrders as $vendorOrder)
                <x-admin.panel :title="$vendorOrder->vendor?->name ?? __('hanbell.vendor.brand')" padding="none">
                    <x-slot:actions>
                        <x-ui.badge :class="$vendorOrder->status->badgeClasses()" size="sm">{{ $vendorOrder->status->label() }}</x-ui.badge>
                    </x-slot:actions>

                    <ul class="divide-y divide-ink-100">
                        @foreach ($vendorOrder->items as $item)
                            <li class="flex items-center gap-4 px-5 py-3.5">
                                <div class="hb-frame size-12 shrink-0 rounded-lg">
                                    <img src="{{ $item->imageUrl() }}" alt="" loading="lazy" class="object-cover">
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="clamp-1 text-sm font-medium text-ink-900">{{ $item->product_name }}</p>
                                    <p class="text-xs text-ink-500">
                                        @if ($item->variant_label){{ $item->variant_label }} · @endif
                                        @if ($item->sku){{ $item->sku }} · @endif
                                        {{ $item->quantity }} × {{ $item->formattedUnitPrice() }}
                                    </p>
                                </div>

                                <span class="tnum shrink-0 text-sm font-semibold text-ink-900">{{ $item->formattedLineTotal() }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <dl class="grid gap-3 border-t border-ink-100 p-5 text-xs sm:grid-cols-3">
                        <div>
                            <dt class="font-semibold uppercase tracking-wider text-ink-400">Subtotal</dt>
                            <dd class="mt-0.5 font-semibold tabular-nums text-ink-900">{{ $vendorOrder->formattedSubtotal() }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold uppercase tracking-wider text-ink-400">Commission ({{ $vendorOrder->commission_percent }}%)</dt>
                            <dd class="mt-0.5 font-semibold tabular-nums text-ink-900">
                                {{ \App\Support\Money::format($vendorOrder->commission_minor, $order->currency) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-semibold uppercase tracking-wider text-ink-400">{{ __('hanbell.admin.vendor_payouts') }}</dt>
                            <dd class="mt-0.5 font-semibold tabular-nums text-brand-700">{{ $vendorOrder->formattedPayout() }}</dd>
                        </div>
                    </dl>

                    {{-- Fulfilment details --}}
                    <div class="border-t border-ink-100 p-5">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-ui.input wire:model="courier" name="courier_{{ $vendorOrder->id }}" :label="__('hanbell.shipping.courier')" />
                            <x-ui.input wire:model="trackingNumber" name="tracking_{{ $vendorOrder->id }}" :label="__('hanbell.shipping.tracking')" />
                        </div>

                        <x-ui.button wire:click="saveTracking" variant="outline" size="sm" class="mt-3">
                            {{ __('hanbell.common.save') }}
                        </x-ui.button>
                    </div>
                </x-admin.panel>
            @endforeach

            {{-- Payments --}}
            <x-admin.panel :title="__('hanbell.admin.payments')" padding="none">
                @if ($order->payments->isEmpty())
                    <p class="p-5 text-sm text-ink-500">{{ __('hanbell.common.no_items') }}</p>
                @else
                    <ul class="divide-y divide-ink-100">
                        @foreach ($order->payments as $payment)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3.5">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs font-semibold text-ink-900">{{ $payment->reference }}</p>
                                    <p class="mt-0.5 text-xs text-ink-500">
                                        {{ $payment->providerLabel() }}
                                        @if ($payment->channel) · {{ $payment->channel }} @endif
                                        @if ($payment->paid_at) · {{ $payment->paid_at->format('j M Y H:i') }} @endif
                                    </p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <x-ui.badge :class="$payment->status->badgeClasses()" size="sm">{{ $payment->status->label() }}</x-ui.badge>
                                    <span class="tnum text-sm font-bold text-ink-900">{{ $payment->formattedAmount() }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.panel>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            <x-admin.panel :title="__('hanbell.order.total')">
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-500">{{ __('hanbell.order.subtotal') }}</dt>
                        <dd class="tnum font-semibold text-ink-900">{{ $order->formattedSubtotal() }}</dd>
                    </div>

                    @if ($order->discount_minor > 0)
                        <div class="flex justify-between">
                            <dt class="text-ink-500">
                                {{ __('hanbell.order.discount') }}
                                @if ($order->coupon) ({{ $order->coupon->code }}) @endif
                            </dt>
                            <dd class="tnum font-semibold text-brand-700">
                                −{{ \App\Support\Money::format($order->discount_minor, $order->currency) }}
                            </dd>
                        </div>
                    @endif

                    <div class="flex justify-between">
                        <dt class="text-ink-500">{{ __('hanbell.order.shipping') }}</dt>
                        <dd class="tnum font-semibold text-ink-900">
                            {{ $order->shipping_minor === 0 ? __('hanbell.shipping.free') : \App\Support\Money::format($order->shipping_minor, $order->currency) }}
                        </dd>
                    </div>

                    @if ($order->tax_minor > 0)
                        <div class="flex justify-between">
                            <dt class="text-ink-500">{{ __('hanbell.order.tax') }}</dt>
                            <dd class="tnum font-semibold text-ink-900">{{ \App\Support\Money::format($order->tax_minor, $order->currency) }}</dd>
                        </div>
                    @endif

                    <div class="flex justify-between border-t border-ink-100 pt-3">
                        <dt class="font-bold text-ink-950">{{ __('hanbell.order.total') }}</dt>
                        <dd class="tnum text-lg font-extrabold text-ink-950">{{ $order->formattedTotal() }}</dd>
                    </div>

                    <div class="flex justify-between border-t border-ink-100 pt-3">
                        <dt class="font-medium text-ink-500">{{ __('hanbell.admin.commission_earned') }}</dt>
                        <dd class="tnum font-semibold text-brand-700">
                            {{ \App\Support\Money::format($order->commission_minor, $order->currency) }}
                        </dd>
                    </div>
                </dl>
            </x-admin.panel>

            <x-admin.panel :title="__('hanbell.admin.customers')">
                <p class="text-sm font-semibold text-ink-900">{{ $order->customer_name }}</p>
                <a href="mailto:{{ $order->email }}" class="mt-0.5 block text-xs text-brand-700 underline underline-offset-2">{{ $order->email }}</a>
                @if ($order->phone)
                    <p class="mt-0.5 text-xs text-ink-500">{{ $order->phone }}</p>
                @endif

                @if ($order->user)
                    <x-ui.button :href="route('admin.customers.show', $order->user)" variant="outline" size="sm" class="mt-3">
                        {{ __('hanbell.common.details') }}
                    </x-ui.button>
                @else
                    <p class="mt-2 text-xs text-ink-400">Guest checkout</p>
                @endif
            </x-admin.panel>

            <x-admin.panel :title="__('hanbell.order.shipping_to')">
                <address class="not-italic text-sm leading-relaxed text-ink-600">
                    @foreach ($order->shippingAddressLines() as $line)
                        {{ $line }}<br>
                    @endforeach
                </address>

                @if ($order->notes)
                    <div class="mt-3 border-t border-ink-100 pt-3">
                        <p class="text-xs font-semibold uppercase tracking-wider text-ink-400">{{ __('hanbell.checkout.order_notes') }}</p>
                        <p class="mt-1 text-sm text-ink-600">{{ $order->notes }}</p>
                    </div>
                @endif
            </x-admin.panel>
        </div>
    </div>
</div>
