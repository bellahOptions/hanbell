<div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-ink-950 text-lg font-extrabold text-white">
                {{ $customer->initials() }}
            </span>

            <div>
                <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ $customer->name }}</h1>
                <p class="mt-0.5 text-sm text-ink-500">{{ $customer->email }}</p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($customer->roles as $role)
                        <x-ui.badge variant="neutral" size="xs">{{ $role->name }}</x-ui.badge>
                    @endforeach
                    @if ($customer->hasTwoFactorEnabled())
                        <x-ui.badge variant="info" size="xs">{{ $customer->twoFactorMethod()->label() }}</x-ui.badge>
                    @endif
                </div>
            </div>
        </div>

        <div class="text-right">
            <p class="text-xs font-semibold uppercase tracking-wider text-ink-400">Lifetime value</p>
            <p class="text-xl font-extrabold tabular-nums text-ink-950">{{ \App\Support\Money::format($spentMinor) }}</p>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <x-admin.panel :title="__('hanbell.order.orders')" padding="none">
                @if ($orders->isEmpty())
                    <x-ui.empty-state icon="cube" :title="__('hanbell.order.no_orders')" />
                @else
                    <ul class="divide-y divide-ink-100">
                        @foreach ($orders as $order)
                            <li class="flex items-center justify-between gap-4 px-5 py-3.5">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="text-sm font-semibold text-ink-900 transition hover:text-brand-700">
                                        {{ $order->number }}
                                    </a>
                                    <p class="text-xs text-ink-500">
                                        {{ $order->created_at->format('j M Y') }} · {{ $order->items_count }} item(s)
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-3">
                                    <x-ui.badge :class="$order->status->badgeClasses()" size="xs">{{ $order->status->label() }}</x-ui.badge>
                                    <span class="tnum text-sm font-bold text-ink-900">{{ $order->formattedTotal() }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.panel>

            <x-admin.panel :title="__('hanbell.account.audit_log')" padding="none">
                @if ($auditTrail->isEmpty())
                    <p class="p-5 text-sm text-ink-500">{{ __('hanbell.common.no_items') }}</p>
                @else
                    <ul class="divide-y divide-ink-100">
                        @foreach ($auditTrail as $log)
                            <li class="flex items-start justify-between gap-4 px-5 py-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-ink-800">{{ $log->label() }}</p>
                                    <p class="mt-0.5 font-mono text-[10px] text-ink-400">{{ $log->event }}</p>
                                </div>
                                <p class="shrink-0 text-[11px] text-ink-400">{{ $log->created_at?->diffForHumans() }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.panel>
        </div>

        <div class="space-y-5">
            <x-admin.panel :title="__('hanbell.account.addresses')">
                @if ($customer->addresses->isEmpty())
                    <p class="text-sm text-ink-500">{{ __('hanbell.account.no_addresses') }}</p>
                @else
                    <ul class="space-y-4">
                        @foreach ($customer->addresses as $address)
                            <li class="text-sm">
                                <p class="font-semibold text-ink-800">
                                    {{ $address->recipient_name }}
                                    @if ($address->is_default)
                                        <x-ui.badge variant="brand" size="xs" class="ml-1">{{ __('hanbell.account.default_address') }}</x-ui.badge>
                                    @endif
                                </p>
                                <p class="mt-0.5 text-xs leading-relaxed text-ink-500">{{ $address->singleLine() }}</p>
                                <p class="text-xs text-ink-400">{{ $address->phone }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.panel>

            <x-admin.panel :title="__('hanbell.account.login_activity')" padding="none">
                @if ($loginAttempts->isEmpty())
                    <p class="p-5 text-sm text-ink-500">{{ __('hanbell.account.no_login_activity') }}</p>
                @else
                    <ul class="divide-y divide-ink-100">
                        @foreach ($loginAttempts as $attempt)
                            <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                                <span class="min-w-0">
                                    <span class="block text-xs font-medium text-ink-700">{{ $attempt->ip_address }}</span>
                                    <span class="block text-[10px] text-ink-400">{{ $attempt->created_at?->diffForHumans() }}</span>
                                </span>

                                <x-ui.badge :variant="$attempt->successful ? 'brand' : 'danger'" size="xs">
                                    {{ $attempt->successful ? __('hanbell.common.yes') : __('hanbell.common.no') }}
                                </x-ui.badge>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.panel>
        </div>
    </div>
</div>
