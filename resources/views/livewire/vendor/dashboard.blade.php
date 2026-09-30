<div class="space-y-6">
    @if (! $vendor)
        <x-ui.alert variant="warning" :title="__('hanbell.vendor.application_status')">
            {{ __('hanbell.vendor.already_applied') }}
        </x-ui.alert>
    @else
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ $vendor->name }}</h1>
            <p class="mt-1 text-sm text-ink-500">
                {{ $vendor->location() }} · {{ $vendor->commissionPercent() !== null
                    ? $vendor->commissionPercent().'% commission'
                    : config('hanbell.commission.default_percent').'% commission (store default)' }}
            </p>
        </div>

        @if (! $vendor->isApproved())
            <x-ui.alert variant="warning" :title="__('hanbell.vendor.application_status')">
                {{ $vendor->status_reason ?: __('hanbell.vendor.pending_hint') }}
            </x-ui.alert>
        @endif

        {{-- KPIs --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-admin.stat :label="__('hanbell.product.products')" :value="$productCount" icon="tag" tone="brand"
                          :hint="$publishedCount.' published · '.$pendingCount.' awaiting review'" />
            <x-admin.stat :label="__('hanbell.admin.orders_count')" :value="$orderCount" icon="shopping-cart" tone="info" />
            <x-admin.stat :label="__('hanbell.admin.revenue')" :value="\App\Support\Money::compact($grossMinor)" icon="banknotes" tone="accent" />
            <x-admin.stat :label="__('hanbell.admin.vendor_payouts')" :value="\App\Support\Money::compact($payoutMinor)" icon="receipt-percent" tone="brand"
                          :hint="\App\Support\Money::format($commissionMinor).' commission'" />
        </div>

        <div class="grid gap-5 lg:grid-cols-3">
            {{-- Recent orders --}}
            <div class="lg:col-span-2">
                <x-admin.panel :title="__('hanbell.admin.orders')" padding="none">
                    <x-slot:actions>
                        <a href="{{ route('vendor.orders.index') }}" wire:navigate class="text-xs font-semibold text-brand-700 hover:text-brand-800">
                            {{ __('hanbell.common.view_all') }}
                        </a>
                    </x-slot:actions>

                    @if ($recentOrders->isEmpty())
                        <x-ui.empty-state icon="shopping-cart" :title="__('hanbell.order.no_orders')" />
                    @else
                        <ul class="divide-y divide-ink-100">
                            @foreach ($recentOrders as $vendorOrder)
                                <li class="flex items-center justify-between gap-4 px-5 py-3.5">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-ink-900">{{ $vendorOrder->number }}</span>
                                        <span class="block text-xs text-ink-500">
                                            {{ $vendorOrder->order?->customer_name }} · {{ $vendorOrder->created_at->format('j M Y') }}
                                        </span>
                                    </span>

                                    <span class="shrink-0 text-right">
                                        <span class="block text-sm font-bold tabular-nums text-ink-900">{{ $vendorOrder->formattedPayout() }}</span>
                                        <x-ui.badge :class="$vendorOrder->status->badgeClasses()" size="xs">{{ $vendorOrder->status->label() }}</x-ui.badge>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-admin.panel>
            </div>

            {{-- Low stock --}}
            <x-admin.panel :title="__('hanbell.admin.low_stock')" padding="none">
                @if ($lowStock->isEmpty())
                    <p class="p-5 text-sm text-ink-500">{{ __('hanbell.common.no_items') }}</p>
                @else
                    <ul class="divide-y divide-ink-100">
                        @foreach ($lowStock as $inventory)
                            <li class="flex items-center justify-between gap-3 px-5 py-3">
                                <span class="min-w-0">
                                    <span class="clamp-1 block text-sm font-medium text-ink-900">{{ $inventory->product?->name }}</span>
                                    @if ($inventory->variant)
                                        <span class="block text-xs text-ink-500">{{ $inventory->variant->label() }}</span>
                                    @endif
                                </span>

                                <span @class([
                                    'shrink-0 rounded-full px-2 py-0.5 text-xs font-bold tabular-nums',
                                    'bg-danger-50 text-danger-600' => $inventory->available() === 0,
                                    'bg-warning-50 text-warning-600' => $inventory->available() > 0,
                                ])>{{ $inventory->available() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.panel>
        </div>
    @endif
</div>
