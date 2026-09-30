@php
    use App\Enums\AdPlacementKey;
@endphp

<div class="hb-container py-8">
    <nav class="mb-5 flex items-center gap-1.5 text-xs text-ink-500" aria-label="Breadcrumb">
        <a href="{{ route('storefront.home') }}" wire:navigate class="hover:text-brand-700">{{ __('hanbell.nav.home') }}</a>
        <x-heroicon-m-chevron-right class="size-3" />
        <a href="{{ route('storefront.account.orders') }}" wire:navigate class="hover:text-brand-700">{{ __('hanbell.order.orders') }}</a>
        <x-heroicon-m-chevron-right class="size-3" />
        <span class="font-medium text-ink-800">{{ $order->number }}</span>
    </nav>

    <div class="grid gap-6 lg:grid-cols-12">
        <div class="space-y-5 lg:col-span-8">
            <x-ui.card>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-ink-400">{{ __('hanbell.order.number') }}</p>
                        <p class="mt-1 text-xl font-extrabold tracking-tight text-ink-950">{{ $order->number }}</p>
                        <p class="mt-1 text-xs text-ink-500">
                            {{ __('hanbell.order.placed_on') }} {{ $order->created_at->format('j M Y') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.badge :class="$order->status->badgeClasses()" size="lg">{{ $order->status->label() }}</x-ui.badge>
                        <x-ui.badge :class="$order->payment_status->badgeClasses()" size="lg">{{ $order->payment_status->label() }}</x-ui.badge>
                    </div>
                </div>

                @if ($order->isCancellable())
                    <div class="mt-5 flex flex-wrap gap-2 border-t border-ink-100 pt-4">
                        <x-ui.button
                            wire:click="cancel"
                            wire:confirm="{{ __('hanbell.order.cancel_confirm') }}"
                            variant="outline"
                            size="sm"
                        >
                            {{ __('hanbell.order.cancel_order') }}
                        </x-ui.button>
                    </div>
                @endif

                {{-- Invoices, receipts and the packing slip. --}}
                <div class="mt-5 border-t border-ink-100 pt-4">
                    <p class="mb-2.5 text-xs font-semibold uppercase tracking-wider text-ink-400">
                        {{ __('hanbell.order.invoice') }}
                    </p>

                    <x-order.documents :order="$order" />
                </div>

                @unless ($order->isPaid())
                    <x-ui.alert variant="warning" class="mt-5" :title="__('hanbell.checkout.payment_pending')">
                        {{ __('hanbell.checkout.payment_pending') }}
                    </x-ui.alert>
                @endunless
            </x-ui.card>

            {{-- Items, grouped by the brand that will ship them --}}
            @foreach ($order->vendorOrders as $vendorOrder)
                <x-admin.panel :title="$vendorOrder->vendor?->name ?? __('hanbell.vendor.brand')" padding="none">
                    @if ($vendorOrder->tracking_number)
                        <div class="border-b border-ink-100 bg-ink-50/60 px-5 py-2.5 text-xs text-ink-600">
                            {{ __('hanbell.shipping.courier') }}: <span class="font-semibold">{{ $vendorOrder->courier ?: '—' }}</span>
                            · {{ __('hanbell.shipping.tracking') }}:
                            <span class="font-mono font-semibold text-ink-900">{{ $vendorOrder->tracking_number }}</span>
                        </div>
                    @endif

                    <ul class="divide-y divide-ink-100">
                        @foreach ($vendorOrder->items as $item)
                            <li class="flex items-center gap-4 p-5">
                                <div class="hb-frame size-16 shrink-0 rounded-lg">
                                    <img src="{{ $item->imageUrl() }}" alt="" loading="lazy" class="object-cover">
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="clamp-2 text-sm font-semibold text-ink-900">{{ $item->product_name }}</p>
                                    @if ($item->variant_label)
                                        <p class="text-xs text-ink-500">{{ $item->variant_label }}</p>
                                    @endif
                                    <p class="mt-0.5 text-xs text-ink-500">
                                        {{ $item->quantity }} × {{ $item->formattedUnitPrice() }}
                                    </p>
                                </div>

                                <span class="tnum shrink-0 text-sm font-bold text-ink-900">{{ $item->formattedLineTotal() }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-admin.panel>
            @endforeach
        </div>

        {{-- Summary + delivery --}}
        <aside class="space-y-5 lg:col-span-4">
            <x-ui.card>
                <h2 class="mb-4 text-sm font-bold text-ink-950">{{ __('hanbell.order.total') }}</h2>

                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-500">{{ __('hanbell.order.subtotal') }}</dt>
                        <dd class="tnum font-semibold text-ink-900">{{ $order->formattedSubtotal() }}</dd>
                    </div>

                    @if ($order->discount_minor > 0)
                        <div class="flex justify-between">
                            <dt class="text-ink-500">{{ __('hanbell.order.discount') }}</dt>
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
                </dl>

                @if ($order->payments->isNotEmpty())
                    <div class="mt-4 border-t border-ink-100 pt-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-ink-400">
                            {{ __('hanbell.order.payment_method') }}
                        </p>
                        <p class="mt-1.5 text-sm font-medium text-ink-800">
                            {{ $order->payments->first()->providerLabel() }}
                        </p>
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card>
                <h2 class="mb-3 text-sm font-bold text-ink-950">{{ __('hanbell.order.shipping_to') }}</h2>
                <address class="not-italic text-sm leading-relaxed text-ink-600">
                    @foreach ($order->shippingAddressLines() as $line)
                        {{ $line }}<br>
                    @endforeach
                </address>
            </x-ui.card>

            <x-ui.card>
                <h2 class="mb-2 text-sm font-bold text-ink-950">{{ __('hanbell.order.need_help') }}</h2>
                <a href="{{ route('storefront.contact') }}" wire:navigate class="text-sm font-semibold text-brand-700 underline underline-offset-2">
                    {{ __('hanbell.order.contact_support') }}
                </a>
            </x-ui.card>

            <x-ads.slot :placement="AdPlacementKey::AccountSidebar" :limit="1" />
        </aside>
    </div>
</div>
