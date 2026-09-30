<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.vendors') }}</h1>
        <p class="mt-1 text-sm text-ink-500">{{ $vendors->total() }} brand(s). Applications are listed first.</p>
    </div>

    <div class="flex flex-wrap gap-1.5">
        <button type="button" wire:click="$set('status', '')"
            @class(['rounded-lg px-3 py-1.5 text-xs font-semibold transition', 'bg-ink-950 text-white' => $status === '', 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100' => $status !== ''])>
            {{ __('hanbell.common.all') }} ({{ $counts['all'] ?? 0 }})
        </button>

        @foreach (\App\Enums\VendorStatus::cases() as $case)
            @if (($counts[$case->value] ?? 0) > 0)
                <button type="button" wire:click="$set('status', '{{ $case->value }}')"
                    @class(['rounded-lg px-3 py-1.5 text-xs font-semibold transition', 'bg-ink-950 text-white' => $status === $case->value, 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100' => $status !== $case->value])>
                    {{ $case->label() }} ({{ $counts[$case->value] }})
                </button>
            @endif
        @endforeach
    </div>

    <x-admin.panel padding="none">
        <div class="border-b border-ink-100 p-4">
            <x-admin.toolbar search="search" searchPlaceholder="Brand name, city or state…" />
        </div>

        @if ($vendors->isEmpty())
            <x-ui.empty-state icon="building-storefront" :title="__('hanbell.vendor.no_vendors')" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-ink-200 bg-ink-50/60">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-500">
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.vendor.brand') }}</th>
                            <th scope="col" class="px-4 py-2.5">Location</th>
                            <th scope="col" class="px-4 py-2.5 text-right">{{ __('hanbell.product.products') }}</th>
                            <th scope="col" class="px-4 py-2.5">Commission</th>
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.common.status') }}</th>
                            <th scope="col" class="px-4 py-2.5 text-right">{{ __('hanbell.common.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100">
                        @foreach ($vendors as $vendor)
                            <tr class="transition hover:bg-ink-50/60">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($vendor->logo_path)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($vendor->logo_path) }}" alt="" class="size-9 shrink-0 rounded-lg object-cover">
                                        @else
                                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-xs font-bold text-white">
                                                {{ \Illuminate\Support\Str::substr($vendor->name, 0, 2) }}
                                            </span>
                                        @endif

                                        <div class="min-w-0">
                                            <a href="{{ route('admin.vendors.show', $vendor) }}" wire:navigate class="clamp-1 font-semibold text-ink-900 transition hover:text-brand-700">
                                                {{ $vendor->name }}
                                            </a>
                                            <p class="clamp-1 text-xs text-ink-500">{{ $vendor->owner?->email ?? $vendor->email }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-xs text-ink-600">{{ $vendor->location() }}</td>

                                <td class="px-4 py-3 text-right text-sm tabular-nums text-ink-700">
                                    {{ $vendor->published_count }} <span class="text-ink-400">/ {{ $vendor->products_count }}</span>
                                </td>

                                <td class="px-4 py-3 text-xs tabular-nums text-ink-600">
                                    {{ $vendor->commission_percent !== null
                                        ? $vendor->commission_percent.'%'
                                        : config('hanbell.commission.default_percent').'% (default)' }}
                                </td>

                                <td class="px-4 py-3">
                                    <x-ui.badge :class="$vendor->status->badgeClasses()" size="sm">{{ $vendor->status->label() }}</x-ui.badge>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($vendor->status->value !== 'approved')
                                            <button type="button" wire:click="approve({{ $vendor->id }})"
                                                class="rounded-md p-1.5 text-brand-600 transition hover:bg-brand-50" title="Approve">
                                                <x-heroicon-o-check-circle class="size-4.5" />
                                            </button>
                                        @endif

                                        @if ($vendor->status->value === 'pending')
                                            <button type="button" wire:click="startReject({{ $vendor->id }})"
                                                class="rounded-md p-1.5 text-danger-500 transition hover:bg-danger-50" title="Reject">
                                                <x-heroicon-o-x-circle class="size-4.5" />
                                            </button>
                                        @endif

                                        @if ($vendor->status->value === 'approved')
                                            <button type="button" wire:click="suspend({{ $vendor->id }})"
                                                class="rounded-md p-1.5 text-ink-400 transition hover:bg-ink-100" title="Suspend">
                                                <x-heroicon-o-pause-circle class="size-4.5" />
                                            </button>
                                        @endif

                                        <a href="{{ route('admin.vendors.show', $vendor) }}" wire:navigate
                                            class="rounded-md p-1.5 text-ink-400 transition hover:bg-ink-100 hover:text-ink-700">
                                            <x-heroicon-o-eye class="size-4.5" />
                                        </a>
                                    </div>

                                    @if ($rejectingId === $vendor->id)
                                        <div class="mt-2 space-y-2">
                                            <label for="reject-{{ $vendor->id }}" class="sr-only">Reason</label>
                                            <input id="reject-{{ $vendor->id }}" type="text" wire:model="reason"
                                                placeholder="Reason for rejection"
                                                class="h-8 w-full rounded-md border border-ink-300 px-2 text-xs focus:border-danger-500 focus:outline-none">

                                            <div class="flex gap-2">
                                                <button type="button" wire:click="reject"
                                                    class="rounded-md bg-danger-600 px-2 py-1 text-xs font-semibold text-white">
                                                    {{ __('hanbell.common.confirm') }}
                                                </button>
                                                <button type="button" wire:click="cancelReject" class="rounded-md px-2 py-1 text-xs text-ink-500">
                                                    {{ __('hanbell.common.cancel') }}
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 p-4">{{ $vendors->links() }}</div>
        @endif
    </x-admin.panel>
</div>
