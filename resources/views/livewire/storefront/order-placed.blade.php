@php
    $statusTone = match ($order->status->value) {
        'paid', 'delivered' => 'brand',
        'pending', 'processing' => 'warning',
        'cancelled' => 'neutral',
        'refunded' => 'danger',
        default => 'info',
    };
@endphp

<div class="hb-container py-8 sm:py-10">
    <div class="mx-auto max-w-3xl">
        {{-- Confirmation banner --}}
        <div class="text-center">
            <span class="mx-auto mb-5 flex size-16 items-center justify-center rounded-2xl bg-brand-600 text-white">
                <x-heroicon-o-check class="size-8" />
            </span>

            <h1 class="text-2xl font-extrabold tracking-tight text-ink-950 sm:text-3xl">
                {{ __('hanbell.order.confirmation_title') }}
            </h1>

            <p class="mx-auto mt-3 max-w-md text-sm text-ink-500">
                {{ __('hanbell.order.confirmation_hint') }}
            </p>

            <p class="mt-4 inline-flex items-center gap-2 rounded-full bg-ink-100 px-4 py-2 text-sm font-bold text-ink-900">
                <x-heroicon-o-hashtag class="size-4 text-ink-400" />
                {{ $order->number }}
            </p>
        </div>

        {{-- Status --}}
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <x-ui.badge :variant="$statusTone" size="lg">{{ $order->status->label() }}</x-ui.badge>
            <x-ui.badge :variant="$order->payment_status->isSuccessful() ? 'brand' : 'warning'" size="lg">
                {{ $order->payment_status->label() }}
            </x-ui.badge>
        </div>

        {{-- Offline payment instructions, if that is the rail that was chosen --}}
        @php $method = $order->payments->first(); @endphp
        @if ($method && ! $order->isPaid() && in_array($method->provider->value, ['bank_transfer', 'cash_on_delivery'], true))
            <x-ui.alert variant="info" :title="__('hanbell.checkout.offline_instructions')" class="mt-8">
                <p class="whitespace-pre-line">{{ $method->gateway_payload['instructions'] ?? '' }}</p>
            </x-ui.alert>
        @endif

        {{-- Pending online payment --}}
        @if (! $order->isPaid() && $method && $method->provider->isOnline())
            <x-ui.alert variant="warning" :title="__('hanbell.checkout.payment_pending')" class="mt-8">
                {{ __('hanbell.checkout.payment_pending') }}
            </x-ui.alert>
        @endif

        {{-- Order summary --}}
        <x-ui.card class="mt-8" padding="none">
            <div class="border-b border-ink-100 px-5 py-4">
                <h2 class="text-sm font-bold text-ink-950">{{ __('hanbell.checkout.review_order') }}</h2>
            </div>

            <ul class="divide-y divide-ink-100">
                @foreach ($order->items as $item)
                    <li class="flex items-center gap-4 p-5">
                        <div class="hb-frame size-16 shrink-0 rounded-lg">
                            <img src="{{ $item->imageUrl() }}" alt="" class="object-cover">
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="clamp-2 text-sm font-semibold text-ink-900">{{ $item->product_name }}</p>
                            @if ($item->variant_label)
                                <p class="text-xs text-ink-500">{{ $item->variant_label }}</p>
                            @endif
                            <p class="mt-0.5 text-xs text-ink-500">
                                {{ $item->vendor_name }} · {{ $item->quantity }} × {{ $item->formattedUnitPrice() }}
                            </p>
                        </div>

                        <span class="tnum shrink-0 text-sm font-bold text-ink-900">{{ $item->formattedLineTotal() }}</span>
                    </li>
                @endforeach
            </ul>

            <dl class="space-y-2.5 border-t border-ink-100 p-5 text-sm">
                <div class="flex justify-between">
                    <dt class="text-ink-500">{{ __('hanbell.order.subtotal') }}</dt>
                    <dd class="tnum font-semibold text-ink-900">{{ $order->formattedSubtotal() }}</dd>
                </div>

                @if ($order->discount_minor > 0)
                    <div class="flex justify-between">
                        <dt class="text-ink-500">{{ __('hanbell.order.discount') }}</dt>
                        <dd class="tnum font-semibold text-brand-700">−{{ \App\Support\Money::format($order->discount_minor, $order->currency) }}</dd>
                    </div>
                @endif

                <div class="flex justify-between">
                    <dt class="text-ink-500">{{ __('hanbell.order.shipping') }}</dt>
                    <dd class="tnum font-semibold text-ink-900">
                        {{ $order->shipping_minor === 0 ? __('hanbell.shipping.free') : \App\Support\Money::format($order->shipping_minor, $order->currency) }}
                    </dd>
                </div>

                <div class="flex justify-between border-t border-ink-100 pt-3">
                    <dt class="font-bold text-ink-950">{{ __('hanbell.order.total') }}</dt>
                    <dd class="tnum text-lg font-extrabold text-ink-950">{{ $order->formattedTotal() }}</dd>
                </div>
            </dl>
        </x-ui.card>

        {{-- Delivery --}}
        <x-ui.card class="mt-5">
            <h2 class="mb-3 text-sm font-bold text-ink-950">{{ __('hanbell.order.shipping_to') }}</h2>
            <address class="not-italic text-sm leading-relaxed text-ink-600">
                @foreach ($order->shippingAddressLines() as $line)
                    {{ $line }}<br>
                @endforeach
            </address>
        </x-ui.card>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <x-ui.button :href="route('storefront.shop')" variant="primary" size="lg" block>
                {{ __('hanbell.order.continue_shopping') }}
            </x-ui.button>
        </div>

        {{-- Whatever documents exist for this order's current state. --}}
        <div class="mt-6">
            <p class="mb-2.5 text-xs font-semibold uppercase tracking-wider text-ink-400">
                {{ __('hanbell.order.invoice') }}
            </p>

            <x-order.documents :order="$order" />
        </div>
    </div>
</div>
