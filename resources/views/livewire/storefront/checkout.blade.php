@php
    use App\Enums\AdPlacementKey;
    $states = ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'];
@endphp

<div class="hb-container py-8">
    {{-- Steps --}}
    <ol class="mb-8 flex items-center gap-2 text-xs font-semibold sm:gap-4">
        @foreach ([
            1 => __('hanbell.checkout.contact'),
            2 => __('hanbell.checkout.shipping_address'),
            3 => __('hanbell.checkout.payment_method'),
        ] as $number => $label)
            <li class="flex items-center gap-2">
                <span @class([
                    'flex size-6 items-center justify-center rounded-full text-[11px] font-bold',
                    'bg-brand-600 text-white' => $number === 1,
                    'bg-ink-200 text-ink-500' => $number !== 1,
                ])>{{ $number }}</span>
                <span class="{{ $number === 1 ? 'text-ink-900' : 'text-ink-400' }}">{{ $label }}</span>
                @if ($number < 3)
                    <x-heroicon-m-chevron-right class="size-3 text-ink-300" />
                @endif
            </li>
        @endforeach
    </ol>

    <h1 class="mb-6 text-2xl font-bold tracking-tight text-ink-950 sm:text-3xl">
        {{ __('hanbell.checkout.title') }}
    </h1>

    <form wire:submit="placeOrder" class="grid gap-6 lg:grid-cols-12 lg:gap-8">
        <div class="space-y-5 lg:col-span-8">
            {{-- ================= Contact ================= --}}
            <x-ui.card padding="md">
                <h2 class="mb-4 text-base font-bold text-ink-950">{{ __('hanbell.checkout.contact') }}</h2>
                <p class="mb-4 text-xs text-ink-500">{{ __('hanbell.checkout.contact_hint') }}</p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input wire:model="name" name="name" :label="__('hanbell.common.name')" required autocomplete="name" />
                    <x-ui.input wire:model="email" name="email" type="email" :label="__('hanbell.common.email')" required autocomplete="email" />
                    <x-ui.input wire:model="phone" name="phone" type="tel" :label="__('hanbell.common.phone')" required autocomplete="tel" class="sm:col-span-2" />
                </div>
            </x-ui.card>

            {{-- ================= Delivery ================= --}}
            <x-ui.card padding="md">
                <h2 class="mb-4 text-base font-bold text-ink-950">{{ __('hanbell.checkout.shipping_address') }}</h2>

                @if ($addresses->isNotEmpty())
                    <div class="mb-5 space-y-2">
                        @foreach ($addresses as $address)
                            <label @class([
                                'flex cursor-pointer items-start gap-3 rounded-xl border p-3.5 transition',
                                'border-brand-600 bg-brand-50' => $selectedAddressId === $address->id,
                                'border-ink-200 hover:border-ink-300' => $selectedAddressId !== $address->id,
                            ])>
                                <input
                                    type="radio"
                                    name="address"
                                    wire:click="selectAddress({{ $address->id }})"
                                    @checked($selectedAddressId === $address->id)
                                    class="mt-0.5 size-4 text-brand-600 focus:ring-brand-600/30"
                                >
                                <span class="min-w-0 flex-1 text-sm">
                                    <span class="flex items-center gap-2 font-semibold text-ink-900">
                                        {{ $address->recipient_name }}
                                        @if ($address->is_default)
                                            <x-ui.badge variant="brand" size="xs">{{ __('hanbell.account.default_address') }}</x-ui.badge>
                                        @endif
                                    </span>
                                    <span class="mt-0.5 block text-xs text-ink-500">{{ $address->singleLine() }}</span>
                                    <span class="mt-0.5 block text-xs text-ink-500">{{ $address->phone }}</span>
                                </span>
                            </label>
                        @endforeach

                        <button
                            type="button"
                            wire:click="useNewAddressForm"
                            @class([
                                'flex w-full items-center gap-2 rounded-xl border border-dashed p-3.5 text-sm font-semibold transition',
                                'border-brand-600 text-brand-700' => $useNewAddress,
                                'border-ink-300 text-ink-500 hover:border-ink-400' => ! $useNewAddress,
                            ])
                        >
                            <x-heroicon-o-plus class="size-4" />
                            {{ __('hanbell.checkout.new_address') }}
                        </button>
                    </div>
                @endif

                @if ($useNewAddress || $addresses->isEmpty())
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.input wire:model="recipient_name" name="recipient_name" :label="__('hanbell.checkout.fields.recipient_name')" required autocomplete="name" />
                        <x-ui.input wire:model="shipping_phone" name="shipping_phone" type="tel" :label="__('hanbell.common.phone')" required autocomplete="tel" />
                        <x-ui.input wire:model="line1" name="line1" :label="__('hanbell.checkout.fields.line1')" required autocomplete="address-line1" class="sm:col-span-2" />
                        <x-ui.input wire:model="line2" name="line2" :label="__('hanbell.checkout.fields.line2')" autocomplete="address-line2" class="sm:col-span-2" />
                        <x-ui.input wire:model="city" name="city" :label="__('hanbell.checkout.fields.city')" required autocomplete="address-level2" />

                        <x-ui.select wire:model.live="state" name="state" :label="__('hanbell.checkout.fields.state')" required :placeholder="__('hanbell.checkout.fields.state')">
                            @foreach ($states as $stateOption)
                                <option value="{{ $stateOption }}">{{ $stateOption }}</option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.input wire:model="postal_code" name="postal_code" :label="__('hanbell.checkout.fields.postal_code')" autocomplete="postal-code" />

                        <x-ui.select wire:model="country" name="country" :label="__('hanbell.checkout.fields.country')" required>
                            <option value="NG">Nigeria</option>
                            <option value="GH">Ghana</option>
                            <option value="KE">Kenya</option>
                            <option value="ZA">South Africa</option>
                            <option value="GB">United Kingdom</option>
                            <option value="US">United States</option>
                        </x-ui.select>
                    </div>

                    @auth
                        <label class="mt-4 flex cursor-pointer items-center gap-2.5 text-sm text-ink-600">
                            <input type="checkbox" wire:model="saveAddress" class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600/30">
                            {{ __('hanbell.account.add_address') }}
                        </label>
                    @endauth
                @endif
            </x-ui.card>

            {{-- ================= Payment ================= --}}
            <x-ui.card padding="md">
                <h2 class="mb-1 text-base font-bold text-ink-950">{{ __('hanbell.checkout.payment_method') }}</h2>
                <p class="mb-4 text-xs text-ink-500">{{ __('hanbell.checkout.payment_hint') }}</p>

                @if (empty($methods))
                    {{-- No configured rail. Saying so plainly is far better than
                         presenting a pay button that cannot work. --}}
                    <x-ui.alert variant="warning" :title="__('hanbell.checkout.no_gateway')">
                        {{ __('hanbell.checkout.no_gateway_hint') }}
                    </x-ui.alert>
                @else
                    <div class="space-y-2">
                        @foreach ($methods as $method)
                            @php
                                $disabledByState = $method['provider'] === 'cash_on_delivery'
                                    && ! $cod->supportsState($state);
                            @endphp

                            <label @class([
                                'flex cursor-pointer items-start gap-3 rounded-xl border p-3.5 transition',
                                'border-brand-600 bg-brand-50' => $payment_provider === $method['provider'],
                                'border-ink-200 hover:border-ink-300' => $payment_provider !== $method['provider'] && ! $disabledByState,
                                'cursor-not-allowed opacity-50' => $disabledByState,
                            ])>
                                <input
                                    type="radio"
                                    wire:model.live="payment_provider"
                                    value="{{ $method['provider'] }}"
                                    @disabled($disabledByState)
                                    class="mt-0.5 size-4 text-brand-600 focus:ring-brand-600/30"
                                >

                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2 text-sm font-semibold text-ink-900">
                                        {{ $method['label'] }}
                                        @unless ($method['online'])
                                            <x-ui.badge variant="neutral" size="xs">{{ __('hanbell.checkout.offline_instructions') }}</x-ui.badge>
                                        @endunless
                                    </span>
                                    <span class="mt-0.5 block text-xs text-ink-500">
                                        @if ($disabledByState)
                                            {{ __('hanbell.checkout.cod_unavailable', ['state' => $state]) }}
                                        @else
                                            {{ $method['description'] }}
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="mt-5">
                    <x-ui.textarea wire:model="notes" name="notes" :label="__('hanbell.checkout.order_notes')" :placeholder="__('hanbell.checkout.order_notes_placeholder')" :rows="2" />
                </div>
            </x-ui.card>
        </div>

        {{-- ================= Summary ================= --}}
        <aside class="lg:col-span-4">
            <div class="sticky top-32 space-y-4">
                <x-ui.card>
                    <h2 class="mb-4 text-base font-bold text-ink-950">{{ __('hanbell.checkout.review_order') }}</h2>

                    <ul class="mb-4 max-h-64 space-y-3 overflow-y-auto pr-1">
                        @foreach ($summary['items'] as $line)
                            <li class="flex items-center gap-3">
                                <div class="hb-frame size-12 shrink-0 rounded-lg">
                                    <img
                                        src="{{ $line['product']->primaryImage()?->url() ?? \App\Models\Product::placeholderImageUrl() }}"
                                        alt=""
                                        class="object-cover"
                                    >
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="clamp-1 text-xs font-semibold text-ink-900">{{ $line['product']->name }}</p>
                                    <p class="text-[11px] text-ink-500">
                                        {{ $line['quantity'] }} × {{ \App\Support\Money::format($line['unit_price_minor']) }}
                                    </p>
                                </div>

                                <span class="tnum shrink-0 text-xs font-bold text-ink-900">
                                    {{ \App\Support\Money::format($line['line_total_minor']) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <dl class="space-y-2.5 border-t border-ink-100 pt-4 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-ink-500">{{ __('hanbell.common.subtotal') }}</dt>
                            <dd class="tnum font-semibold text-ink-900">{{ \App\Support\Money::format($summary['subtotal_minor']) }}</dd>
                        </div>

                        @if ($discount > 0)
                            <div class="flex items-center justify-between">
                                <dt class="text-ink-500">{{ __('hanbell.cart.discount') }}</dt>
                                <dd class="tnum font-semibold text-brand-700">−{{ \App\Support\Money::format($discount) }}</dd>
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
                            <dd class="tnum text-lg font-extrabold text-ink-950">{{ \App\Support\Money::format($summary['total_minor']) }}</dd>
                        </div>
                    </dl>

                    @if (! empty($methods))
                        <x-ui.button
                            type="submit"
                            variant="primary"
                            size="lg"
                            block
                            class="mt-5"
                            loading="placeOrder"
                        >
                            <span wire:loading.remove wire:target="placeOrder">
                                {{ __('hanbell.checkout.pay_now', ['amount' => \App\Support\Money::format($summary['total_minor'])]) }}
                            </span>
                            <span wire:loading wire:target="placeOrder">{{ __('hanbell.checkout.placing_order') }}</span>
                        </x-ui.button>
                    @endif

                    <p class="mt-3 text-center text-[11px] leading-relaxed text-ink-400">
                        {!! __('hanbell.checkout.agree_terms', [
                            'terms' => '<a href="'.route('storefront.pages.show', ['slug' => 'terms-of-service']).'" class="underline">'.__('hanbell.checkout.terms').'</a>',
                            'privacy' => '<a href="'.route('storefront.pages.show', ['slug' => 'privacy-policy']).'" class="underline">'.__('hanbell.checkout.privacy').'</a>',
                        ]) !!}
                    </p>
                </x-ui.card>

                <x-ads.slot :placement="AdPlacementKey::CheckoutSidebar" :limit="1" />
            </div>
        </aside>
    </form>
</div>
