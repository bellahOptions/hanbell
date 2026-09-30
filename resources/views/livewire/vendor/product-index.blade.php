<div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.products') }}</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $products->total() }} in your catalogue</p>
        </div>

        <x-ui.button :href="route('vendor.products.create')" variant="primary">
            <x-heroicon-o-plus class="size-4" />
            {{ __('hanbell.common.create') }}
        </x-ui.button>
    </div>

    <x-admin.panel padding="none">
        <div class="border-b border-ink-100 p-4">
            <x-admin.toolbar search="search" />
        </div>

        @if ($products->isEmpty())
            <x-ui.empty-state
                icon="tag"
                :title="__('hanbell.shop.no_products')"
                :message="__('hanbell.vendor.about_brand_hint')"
                :action-label="__('hanbell.common.create')"
                :action-href="route('vendor.products.create')"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-ink-200 bg-ink-50/60">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-ink-500">
                            <th scope="col" class="px-4 py-2.5">{{ __('hanbell.product.product') }}</th>
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
                                            <p class="text-xs text-ink-500">{{ $product->category?->name ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-ink-900">
                                    {{ $product->formattedPrice() }}
                                </td>

                                <td class="px-4 py-3">
                                    <x-ui.badge :class="$product->status->badgeClasses()" size="sm">{{ $product->status->label() }}</x-ui.badge>

                                    @if ($product->status_reason)
                                        <p class="mt-1 max-w-xs text-[11px] text-danger-600">{{ $product->status_reason }}</p>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        @if (in_array($product->status->value, ['draft', 'rejected'], true))
                                            <button
                                                type="button"
                                                wire:click="submitForReview({{ $product->id }})"
                                                class="rounded-md px-2 py-1.5 text-xs font-semibold text-brand-700 transition hover:bg-brand-50"
                                            >
                                                {{ __('hanbell.vendor.submit_application') }}
                                            </button>
                                        @endif

                                        <a
                                            href="{{ route('vendor.products.edit', $product) }}"
                                            wire:navigate
                                            class="rounded-md p-1.5 text-ink-400 transition hover:bg-ink-100 hover:text-ink-700"
                                        >
                                            <x-heroicon-o-pencil-square class="size-4.5" />
                                        </a>
                                    </div>
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
