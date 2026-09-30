@php
    use App\Enums\AdPlacementKey;
@endphp

<div class="hb-container py-8">
    <h1 class="mb-6 text-2xl font-bold tracking-tight text-ink-950 sm:text-3xl">
        {{ __('hanbell.cart.title') }}
    </h1>

    @if (! $cart || $summary['line_count'] === 0)
        <x-ui.card padding="none">
            <x-ui.empty-state
                icon="shopping-bag"
                :title="__('hanbell.cart.empty')"
                :message="__('hanbell.cart.empty_hint')"
                :action-label="__('hanbell.cart.start_shopping')"
                :action-href="route('storefront.shop')"
            />
        </x-ui.card>
    @else
        <div class="grid gap-6 lg:grid-cols-12 lg:gap-8">
            <div class="lg:col-span-8">
                {{-- Free-delivery progress. A concrete figure ("₦12,000 more")
                     is far more useful than a vague nudge. --}}
                <div class="mb-5 rounded-xl border border-ink-200 bg-white p-4">
                    @if ($qualifiesForFreeShipping)
                        <p class="flex items-center gap-2 text-sm font-semibold text-brand-700">
                            <x-heroicon-s-check-circle class="size-4.5" />
                            {{ __('hanbell.cart.free_shipping_unlocked') }}
                        </p>
                    @else
                        <p class="text-sm font-medium text-ink-700">
                            {{ __('hanbell.cart.free_shipping_progress', ['amount' => \App\Support\Money::format($untilFreeShipping)]) }}
                        </p>

                        @php
                            $threshold = (int) config('hanbell.shipping.free_threshold_minor', 5000000);
                            $progress = $threshold > 0 ? min(100, (int) round($summary['subtotal_minor'] / $threshold * 100)) : 0;
                        @endphp

                        <div class="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-ink-100">
                            <div class="h-full rounded-full bg-brand-600 transition-all duration-500" style="width: {{ $progress }}%"></div>
                        </div>
                    @endif
                </div>

                {{-- Lines, grouped by maker: an order from three brands is three
                     parcels, and the bag should say so. --}}
                <div class="space-y-5">
                    @foreach ($summary['by_vendor'] as $group)
                        @if ($group['vendor'])
                            <div class="overflow-hidden rounded-xl border border-ink-200 bg-white">
                                <div class="flex items-center justify-between gap-3 border-b border-ink-100 bg-ink-50/70 px-4 py-3">
                                    <a
                                        href="{{ $group['vendor']->publicUrl() }}"
                                        wire:navigate
                                        class="flex items-center gap-2 text-sm font-bold text-ink-900 transition hover:text-brand-700"
                                    >
                                        <x-heroicon-o-building-storefront class="size-4 text-ink-400" />
                                        {{ $group['vendor']->name }}
                                    </a>

                                    <span class="text-xs font-medium text-ink-500">
                                        {{ \App\Support\Money::format($group['subtotal_minor']) }}
                                    </span>
                                </div>

                                <ul class="divide-y divide-ink-100">
                                    @foreach ($group['items'] as $line)
                                        @php
                                            $product = $line['product'];
                                            $variant = $line['variant'];
                                            $image = $product->primaryImage();
                                        @endphp

                                        <li class="flex gap-4 p-4">
                                            <a href="{{ $line['url'] }}" wire:navigate class="shrink-0">
                                                <div class="hb-frame size-20 rounded-lg sm:size-24">
                                                    <img
                                                        src="{{ $image?->url() ?? \App\Models\Product::placeholderImageUrl() }}"
                                                        alt="{{ $image?->alt() ?? $product->name }}"
                                                        loading="lazy"
                                                        class="object-cover"
                                                    >
                                                </div>
                                            </a>

                                            <div class="flex min-w-0 flex-1 flex-col">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <h2 class="clamp-2 text-sm font-semibold text-ink-900">
                                                            <a href="{{ $line['url'] }}" wire:navigate class="transition hover:text-brand-700">
                                                                {{ $product->name }}
                                                            </a>
                                                        </h2>

                                                        @if ($variant?->label())
                                                            <p class="mt-0.5 text-xs text-ink-500">{{ $variant->label() }}</p>
                                                        @endif

                                                        @if ($line['available'] <= 0)
                                                            <p class="mt-1 flex items-center gap-1 text-xs font-semibold text-danger-600">
                                                                <x-heroicon-o-exclamation-triangle class="size-3.5" />
                                                                {{ __('hanbell.product.out_of_stock') }}
                                                            </p>
                                                        @elseif ($line['available'] < $line['quantity'])
                                                            <p class="mt-1 text-xs font-semibold text-warning-600">
                                                                {{ __('hanbell.product.low_stock', ['count' => $line['available']]) }}
                                                            </p>
                                                        @endif
                                                    </div>

                                                    <x-ui.price :amount="$line['line_total_minor']" :currency="$product->currency" size="sm" class="shrink-0" />
                                                </div>

                                                <div class="mt-auto flex items-center justify-between gap-3 pt-3">
                                                    <div class="flex h-9 items-center rounded-lg border border-ink-300">
                                                        <button
                                                            type="button"
                                                            wire:click="updateQuantity({{ $line['cart_item_id'] }}, {{ $line['quantity'] - 1 }})"
                                                            wire:loading.attr="disabled"
                                                            class="flex size-9 items-center justify-center text-ink-500 transition hover:text-ink-900"
                                                            aria-label="{{ __('hanbell.common.remove') }}"
                                                        >
                                                            <x-heroicon-m-minus class="size-3.5" />
                                                        </button>

                                                        <span class="w-9 text-center text-sm font-bold text-ink-900">{{ $line['quantity'] }}</span>

                                                        <button
                                                            type="button"
                                                            wire:click="updateQuantity({{ $line['cart_item_id'] }}, {{ $line['quantity'] + 1 }})"
                                                            wire:loading.attr="disabled"
                                                            class="flex size-9 items-center justify-center text-ink-500 transition hover:text-ink-900"
                                                            aria-label="{{ __('hanbell.common.add') }}"
                                                        >
                                                            <x-heroicon-m-plus class="size-3.5" />
                                                        </button>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        wire:click="remove({{ $line['cart_item_id'] }})"
                                                        class="flex items-center gap-1.5 text-xs font-semibold text-ink-400 transition hover:text-danger-600"
                                                    >
                                                        <x-heroicon-o-trash class="size-3.5" />
                                                        {{ __('hanbell.cart.remove') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                    <a href="{{ route('storefront.shop') }}" wire:navigate class="flex items-center gap-1.5 text-sm font-semibold text-brand-700 transition hover:text-brand-800">
                        <x-heroicon-m-arrow-left class="size-4" />
                        {{ __('hanbell.cart.continue_shopping') }}
                    </a>

                    <button type="button" wire:click="clear" class="text-xs font-medium text-ink-400 underline underline-offset-2 transition hover:text-danger-600">
                        {{ __('hanbell.common.clear_all') }}
                    </button>
                </div>
            </div>

            {{-- Summary --}}
            <aside class="lg:col-span-4">
                <div class="sticky top-32 space-y-4">
                    <x-ui.card>
                        <h2 class="mb-4 text-base font-bold text-ink-950">{{ __('hanbell.cart.order_summary') }}</h2>

                        {{-- Promo code --}}
                        <div class="mb-4">
                            @if ($coupon)
                                <div class="flex items-center justify-between gap-3 rounded-lg border border-brand-600/25 bg-brand-50 px-3 py-2.5">
                                    <span class="flex items-center gap-2 text-sm font-semibold text-brand-800">
                                        <x-heroicon-o-ticket class="size-4" />
                                        {{ $coupon->code }}
                                    </span>
                                    <button type="button" wire:click="removeCoupon" class="text-xs font-semibold text-brand-700 underline underline-offset-2">
                                        {{ __('hanbell.common.remove') }}
                                    </button>
                                </div>
                            @else
                                <div class="flex gap-2">
                                    <label for="coupon" class="sr-only">{{ __('hanbell.cart.coupon') }}</label>
                                    <input
                                        id="coupon"
                                        type="text"
                                        wire:model="couponCode"
                                        wire:keydown.enter="applyCoupon"
                                        placeholder="{{ __('hanbell.cart.coupon_placeholder') }}"
                                        class="h-10 flex-1 rounded-lg border border-ink-300 px-3 text-sm uppercase placeholder:normal-case focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20"
                                    >
                                    <x-ui.button type="button" wire:click="applyCoupon" variant="outline" size="sm" loading="applyCoupon" class="h-10">
                                        {{ __('hanbell.cart.coupon_apply') }}
                                    </x-ui.button>
                                </div>
                            @endif
                        </div>

                        <dl class="space-y-2.5 text-sm">
                            <div class="flex items-center justify-between">
                                <dt class="text-ink-500">{{ __('hanbell.cart.title') }}</dt>
                                <dd class="tnum font-semibold text-ink-900">
                                    {{ \App\Support\Money::format($summary['subtotal_minor']) }}
                                </dd>
                            </div>

                            @if ($discount > 0)
                                <div class="flex items-center justify-between">
                                    <dt class="text-ink-500">{{ __('hanbell.cart.discount') }}</dt>
                                    <dd class="tnum font-semibold text-brand-700">
                                        −{{ \App\Support\Money::format($discount) }}
                                    </dd>
                                </div>
                            @endif

                            <div class="flex items-center justify-between">
                                <dt class="text-ink-500">{{ __('hanbell.cart.shipping') }}</dt>
                                <dd class="tnum font-semibold text-ink-900">
                                    @if ($summary['shipping_minor'] === 0)
                                        <span class="text-brand-700">{{ __('hanbell.shipping.free') }}</span>
                                    @else
                                        {{ \App\Support\Money::format($summary['shipping_minor']) }}
                                    @endif
                                </dd>
                            </div>

                            <div class="flex items-center justify-between border-t border-ink-100 pt-3">
                                <dt class="font-bold text-ink-950">{{ __('hanbell.common.total') }}</dt>
                                <dd class="tnum text-lg font-extrabold text-ink-950">
                                    {{ \App\Support\Money::format($summary['total_minor']) }}
                                </dd>
                            </div>
                        </dl>

                        <x-ui.button
                            :href="route('storefront.checkout')"
                            variant="primary"
                            size="lg"
                            block
                            class="mt-5"
                        >
                            {{ __('hanbell.cart.proceed_to_checkout') }}
                            <x-heroicon-m-arrow-right class="size-4" />
                        </x-ui.button>

                        @guest
                            <p class="mt-3 text-center text-xs text-ink-500">
                                {{ __('hanbell.cart.sign_in_to_checkout') }}
                            </p>
                        @endguest
                    </x-ui.card>

                    {{-- Cart sidebar ad slot --}}
                    <x-ads.slot :placement="AdPlacementKey::CartSidebar" :limit="1" />
                </div>
            </aside>
        </div>
    @endif
</div>
