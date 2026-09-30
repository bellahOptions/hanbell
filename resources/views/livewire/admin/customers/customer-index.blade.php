<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.customers') }}</h1>
        <p class="mt-1 text-sm text-ink-500">
            {{ number_format($totalCustomers) }} account(s) ·
            {{ number_format($verifiedCount) }} verified ·
            {{ number_format($twoFactorCount) }} with two-factor on
        </p>
    </div>

    <x-admin.panel padding="none">
        <div class="border-b border-ink-100 p-4">
            <x-admin.toolbar search="search" searchPlaceholder="Name, email or phone…" />
        </div>

        @if ($customers->isEmpty())
            <x-ui.empty-state icon="users" :title="__('hanbell.common.no_items')" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-ink-200 bg-ink-50/60">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-500">
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.common.name') }}</th>
                            <th scope="col" class="px-4 py-2.5">Contact</th>
                            <th scope="col" class="px-4 py-2.5 text-right">{{ __('hanbell.order.orders') }}</th>
                            <th scope="col" class="px-4 py-2.5 text-right">Spent</th>
                            <th scope="col" class="px-4 py-2.5">Security</th>
                            <th scope="col" class="px-4 py-2.5">Joined</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100">
                        @foreach ($customers as $customer)
                            <tr class="transition hover:bg-ink-50/60">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-ink-100 text-xs font-bold text-ink-600">
                                            {{ $customer->initials() }}
                                        </span>
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.customers.show', $customer) }}" wire:navigate class="clamp-1 font-semibold text-ink-900 transition hover:text-brand-700">
                                                {{ $customer->name }}
                                            </a>
                                            @if ($customer->roles->isNotEmpty())
                                                <p class="text-[11px] font-medium uppercase tracking-wider text-ink-400">
                                                    {{ $customer->roles->pluck('name')->implode(', ') }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <p class="clamp-1 max-w-48 text-xs text-ink-600">{{ $customer->email }}</p>
                                    @if ($customer->phone)
                                        <p class="text-xs text-ink-400">{{ $customer->phone }}</p>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-right tabular-nums text-ink-700">{{ $customer->orders_count }}</td>

                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-ink-900">
                                    {{ \App\Support\Money::format((int) ($customer->spent_minor ?? 0)) }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @if ($customer->email_verified_at)
                                            <x-ui.badge variant="brand" size="xs">Verified</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="warning" size="xs">Unverified</x-ui.badge>
                                        @endif

                                        @if ($customer->hasTwoFactorEnabled())
                                            <x-ui.badge variant="info" size="xs">2FA</x-ui.badge>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-xs text-ink-500">{{ $customer->created_at->format('j M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 p-4">{{ $customers->links() }}</div>
        @endif
    </x-admin.panel>
</div>
