@php use App\Enums\ProductStatus; @endphp

<div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.products') }}</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $products->total() }} in the catalogue</p>
        </div>

        <x-ui.button :href="route('admin.products.create')" variant="primary">
            <x-heroicon-o-plus class="size-4" />
            {{ __('hanbell.common.create') }}
        </x-ui.button>
    </div>

    {{-- Status tabs. The pending queue is the operator's actual job, so it is
         surfaced as a tab rather than buried behind a filter control. --}}
    <div class="flex flex-wrap gap-1.5">
        <button
            type="button"
            wire:click="$set('status', '')"
            @class([
                'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                'bg-ink-950 text-white' => $status === '',
                'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100' => $status !== '',
            ])
        >
            {{ __('hanbell.common.all') }} ({{ $counts['all'] ?? 0 }})
        </button>

        @foreach (ProductStatus::cases() as $case)
            @if (($counts[$case->value] ?? 0) > 0)
                <button
                    type="button"
                    wire:click="$set('status', '{{ $case->value }}')"
                    @class([
                        'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                        'bg-ink-950 text-white' => $status === $case->value,
                        'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-100' => $status !== $case->value,
                    ])
                >
                    {{ $case->label() }} ({{ $counts[$case->value] }})
                </button>
            @endif
        @endforeach
    </div>

    <x-admin.panel padding="none">
        <div class="border-b border-ink-100 p-4">
            <x-admin.toolbar search="search">
                <select wire:model.live="vendor" class="h-10 rounded-lg border border-ink-300 bg-white px-3 text-sm">
                    <option value="">{{ __('hanbell.admin.vendors') }}</option>
                    @foreach ($vendors as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="sort" class="h-10 rounded-lg border border-ink-300 bg-white px-3 text-sm">
                    <option value="newest">{{ __('hanbell.shop.sort.newest') }}</option>
                    <option value="oldest">{{ __('hanbell.common.date') }}</option>
                    <option value="name">{{ __('hanbell.shop.sort.name') }}</option>
                    <option value="price_high">{{ __('hanbell.shop.sort.price_high') }}</option>
                    <option value="price_low">{{ __('hanbell.shop.sort.price_low') }}</option>
                </select>
            </x-admin.toolbar>
        </div>

        @if ($products->isEmpty())
            <x-ui.empty-state icon="tag" :title="__('hanbell.shop.no_products')" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-ink-200 bg-ink-50/60">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-500">
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.product.product') }}</th>
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.vendor.brand') }}</th>
                            <th scope="col" class="px-4 py-2.5 text-right">{{ __('hanbell.common.price') }}</th>
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.common.status') }}</th>
                            <th scope="col" class="px-4 py-2.5 text-right">{{ __('hanbell.common.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100">
                        @foreach ($products as $product)
                            <tr class="transition hover:bg-ink-50/60">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="hb-frame size-11 shrink-0 rounded-lg">
                                            <img
                                                src="{{ $product->primaryImage()?->url() ?? \App\Models\Product::placeholderImageUrl() }}"
                                                alt=""
                                                loading="lazy"
                                                class="object-cover"
                                            >
                                        </div>

                                        <div class="min-w-0">
                                            <p class="clamp-1 max-w-xs font-medium text-ink-900">{{ $product->name }}</p>
                                            <p class="text-xs text-ink-500">
                                                {{ $product->category?->name ?? '—' }}
                                                @if ($product->variants_count > 0)
                                                    · {{ $product->variants_count }} variants
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-sm text-ink-600">{{ $product->vendor?->name ?? '—' }}</td>

                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-ink-900">
                                    {{ $product->formattedPrice() }}
                                </td>

                                <td class="px-4 py-3">
                                    <x-ui.badge :class="$product->status->badgeClasses()" size="sm">
                                        {{ $product->status->label() }}
                                    </x-ui.badge>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        @if (in_array($product->status, [ProductStatus::PendingReview, ProductStatus::Draft, ProductStatus::Rejected], true))
                                            <button
                                                type="button"
                                                wire:click="approve({{ $product->id }})"
                                                class="rounded-md p-1.5 text-brand-600 transition hover:bg-brand-50"
                                                title="Approve and publish"
                                            >
                                                <x-heroicon-o-check-circle class="size-4.5" />
                                            </button>

                                            <button
                                                type="button"
                                                wire:click="startReject({{ $product->id }})"
                                                class="rounded-md p-1.5 text-danger-500 transition hover:bg-danger-50"
                                                title="Reject"
                                            >
                                                <x-heroicon-o-x-circle class="size-4.5" />
                                            </button>
                                        @endif

                                        @if ($product->isPublished())
                                            <button
                                                type="button"
                                                wire:click="unpublish({{ $product->id }})"
                                                class="rounded-md p-1.5 text-ink-400 transition hover:bg-ink-100"
                                                title="Unpublish"
                                            >
                                                <x-heroicon-o-eye-slash class="size-4.5" />
                                            </button>
                                        @endif

                                        <button
                                            type="button"
                                            wire:click="toggleFeatured({{ $product->id }})"
                                            @class([
                                                'rounded-md p-1.5 transition',
                                                'text-accent-600 hover:bg-accent-100' => $product->is_featured,
                                                'text-ink-400 hover:bg-ink-100' => ! $product->is_featured,
                                            ])
                                            title="Toggle featured"
                                        >
                                            <x-heroicon-o-star class="size-4.5" />
                                        </button>

                                        <a
                                            href="{{ route('admin.products.edit', $product) }}"
                                            wire:navigate
                                            class="rounded-md p-1.5 text-ink-400 transition hover:bg-ink-100 hover:text-ink-700"
                                        >
                                            <x-heroicon-o-pencil-square class="size-4.5" />
                                        </a>
                                    </div>

                                    {{-- Inline rejection: a reason is required, because a
                                         vendor cannot fix a problem they cannot see. --}}
                                    @if ($rejectingId === $product->id)
                                        <div class="mt-2 flex items-center gap-2">
                                            <input
                                                type="text"
                                                wire:model="rejectReasons.{{ $product->id }}"
                                                placeholder="Reason for rejection"
                                                class="h-8 flex-1 rounded-md border border-ink-300 px-2 text-xs focus:border-danger-500 focus:outline-none"
                                            >

                                            <button
                                                type="button"
                                                wire:click="reject({{ $product->id }})"
                                                class="rounded-md bg-danger-600 px-2 py-1 text-xs font-semibold text-white transition hover:bg-danger-500"
                                            >
                                                {{ __('hanbell.common.confirm') }}
                                            </button>

                                            <button type="button" wire:click="cancelReject" class="rounded-md px-2 py-1 text-xs text-ink-500">
                                                {{ __('hanbell.common.cancel') }}
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 p-4">{{ $products->links() }}</div>
        @endif
    </x-admin.panel>
</div>
