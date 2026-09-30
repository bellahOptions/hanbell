<div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.inventory') }}</h1>
            <p class="mt-1 text-sm text-ink-500">
                {{ number_format($totalUnits) }} units on hand ·
                {{ $lowCount }} low · {{ $outCount }} out of stock
            </p>
        </div>
    </div>

    <div class="flex flex-wrap gap-1.5">
        @foreach (['' => __('hanbell.common.all'), 'low' => __('hanbell.admin.low_stock'), 'out' => __('hanbell.product.out_of_stock')] as $value => $label)
            <button
                type="button"
                wire:click="$set('filter', '{{ $value }}')"
                @class([
                    'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                    'bg-ink-950 text-white' => $filter === $value,
                    'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100' => $filter !== $value,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <x-admin.panel padding="none">
        <div class="border-b border-ink-100 p-4">
            <x-admin.toolbar search="search" searchPlaceholder="Search products…" />
        </div>

        @if ($inventories->isEmpty())
            <x-ui.empty-state icon="archive-box" :title="__('hanbell.common.no_items')" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-ink-200 bg-ink-50/60">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-500">
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.product.product') }}</th>
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.vendor.brand') }}</th>
                            <th scope="col" class="px-4 py-2.5 text-right">On hand</th>
                            <th scope="col" class="px-4 py-2.5 text-right">Reserved</th>
                            <th scope="col" class="px-4 py-2.5 text-right">Available</th>
                            <th scope="col" class="px-4 py-2.5">Restock</th>
                            <th scope="col" class="px-4 py-2.5 text-right">Set to</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100">
                        @foreach ($inventories as $inventory)
                            <tr class="transition hover:bg-ink-50/60">
                                <td class="px-4 py-3">
                                    <p class="clamp-1 max-w-xs font-medium text-ink-900">{{ $inventory->product?->name }}</p>
                                    @if ($inventory->variant)
                                        <p class="text-xs text-ink-500">{{ $inventory->variant->label() }}</p>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-xs text-ink-600">{{ $inventory->product?->vendor?->name ?? '—' }}</td>

                                <td class="px-4 py-3 text-right tabular-nums text-ink-700">{{ $inventory->quantity_on_hand }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-ink-500">{{ $inventory->quantity_reserved }}</td>

                                <td class="px-4 py-3 text-right">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-xs font-bold tabular-nums',
                                        'bg-danger-50 text-danger-600' => $inventory->available() === 0,
                                        'bg-warning-50 text-warning-600' => $inventory->available() > 0 && $inventory->isLow(),
                                        'bg-brand-50 text-brand-700' => $inventory->available() > 0 && ! $inventory->isLow(),
                                    ])>{{ $inventory->available() }}</span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex gap-1">
                                        @foreach ([10, 25, 50] as $amount)
                                            <button
                                                type="button"
                                                wire:click="restock({{ $inventory->id }}, {{ $amount }})"
                                                class="rounded-md border border-ink-300 px-2 py-1 text-xs font-semibold text-ink-600 transition hover:border-brand-600 hover:text-brand-700"
                                            >
                                                +{{ $amount }}
                                            </button>
                                        @endforeach
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <label for="qty-{{ $inventory->id }}" class="sr-only">New quantity</label>
                                        <input
                                            id="qty-{{ $inventory->id }}"
                                            type="number"
                                            min="0"
                                            wire:model="quantities.{{ $inventory->id }}"
                                            placeholder="{{ $inventory->quantity_on_hand }}"
                                            class="h-8 w-20 rounded-md border border-ink-300 px-2 text-right text-xs focus:border-brand-600 focus:outline-none"
                                        >

                                        <button
                                            type="button"
                                            wire:click="adjust({{ $inventory->id }})"
                                            class="rounded-md bg-ink-950 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-ink-800"
                                        >
                                            {{ __('hanbell.common.save') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 p-4">{{ $inventories->links() }}</div>
        @endif
    </x-admin.panel>
</div>
