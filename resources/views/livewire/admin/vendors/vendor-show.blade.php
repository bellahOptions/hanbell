<div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ $vendor->name }}</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $vendor->location() }} · approved {{ $vendor->approved_at?->format('j M Y') }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-ui.badge :class="$vendor->status->badgeClasses()" size="lg">{{ $vendor->status->label() }}</x-ui.badge>

            @if ($vendor->isApproved())
                <x-ui.button :href="$vendor->publicUrl()" variant="outline" size="sm">
                    {{ __('hanbell.admin.back_to_store') }}
                </x-ui.button>
            @endif
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.stat :label="__('hanbell.admin.revenue')" :value="\App\Support\Money::compact($grossMinor)" icon="banknotes" tone="brand" />
        <x-admin.stat :label="__('hanbell.admin.commission_earned')" :value="\App\Support\Money::compact($commissionMinor)" icon="receipt-percent" tone="accent" />
        <x-admin.stat :label="__('hanbell.admin.vendor_payouts')" :value="\App\Support\Money::compact($payoutMinor)" icon="arrow-up-tray" tone="info" />
        <x-admin.stat :label="__('hanbell.admin.orders_count')" :value="number_format($orderCount)" icon="shopping-cart" tone="neutral" />
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <x-admin.panel :title="__('hanbell.vendor.brand_story')">
                @if ($vendor->description)
                    <p class="text-sm leading-relaxed text-ink-700">{{ $vendor->description }}</p>
                @endif

                @if ($vendor->story)
                    <p class="mt-4 whitespace-pre-line border-t border-ink-100 pt-4 text-sm leading-relaxed text-ink-600">{{ $vendor->story }}</p>
                @endif
            </x-admin.panel>

            <x-admin.panel :title="__('hanbell.product.products')" padding="none">
                @if ($recentProducts->isEmpty())
                    <p class="p-5 text-sm text-ink-500">{{ __('hanbell.shop.no_products') }}</p>
                @else
                    <div class="grid grid-cols-2 gap-4 p-5 sm:grid-cols-4">
                        @foreach ($recentProducts as $product)
                            <x-product.card :product="$product" />
                        @endforeach
                    </div>
                @endif
            </x-admin.panel>
        </div>

        <div class="space-y-5">
            <x-admin.panel :title="__('hanbell.common.details')">
                <dl class="space-y-3 text-sm">
                    @foreach (array_filter([
                        'Owner' => $vendor->owner?->name,
                        'Email' => $vendor->email,
                        'Phone' => $vendor->phone,
                        'WhatsApp' => $vendor->whatsapp,
                        'Website' => $vendor->website,
                        'City' => $vendor->city,
                        'State' => $vendor->state,
                        'Legal name' => $vendor->legal_name,
                    ]) as $label => $value)
                        <div class="flex items-start justify-between gap-4">
                            <dt class="shrink-0 text-ink-500">{{ $label }}</dt>
                            <dd class="clamp-1 text-right font-medium text-ink-900">{{ $value }}</dd>
                        </div>
                    @endforeach

                    <div class="flex items-start justify-between gap-4 border-t border-ink-100 pt-3">
                        <dt class="text-ink-500">Commission</dt>
                        <dd class="font-semibold text-ink-900">
                            {{ $vendor->commission_percent !== null
                                ? $vendor->commission_percent.'%'
                                : config('hanbell.commission.default_percent').'% (store default)' }}
                        </dd>
                    </div>
                </dl>
            </x-admin.panel>

            @if ($vendor->brands->isNotEmpty())
                <x-admin.panel :title="__('hanbell.admin.brands')">
                    <ul class="space-y-2 text-sm">
                        @foreach ($vendor->brands as $brand)
                            <li class="text-ink-700">{{ $brand->name }}</li>
                        @endforeach
                    </ul>
                </x-admin.panel>
            @endif
        </div>
    </div>
</div>
