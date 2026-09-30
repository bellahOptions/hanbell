@php
    $inStock = $product?->isInStock($variantId ? \App\Models\ProductVariant::find($variantId) : null) ?? false;
@endphp

@if ($compact)
    {{-- One-click variant: a round icon button for product tiles. --}}
    <button
        type="button"
        wire:click="add"
        wire:loading.attr="disabled"
        wire:target="add"
        @disabled(! $inStock)
        class="absolute inset-x-2.5 bottom-2.5 z-20 flex h-9 items-center justify-center gap-1.5 rounded-lg bg-ink-950/92 text-xs font-bold text-white opacity-0 backdrop-blur-sm transition-all duration-200 group-hover:opacity-100 hover:bg-brand-600 focus:opacity-100 disabled:cursor-not-allowed disabled:opacity-60"
        aria-label="{{ __('hanbell.product.add_to_bag') }}"
    >
        <span wire:loading.remove wire:target="add" class="flex items-center gap-1.5">
            <x-heroicon-o-shopping-bag class="size-4" />
            {{ $inStock ? __('hanbell.product.add_to_bag') : __('hanbell.product.out_of_stock') }}
        </span>

        <svg wire:loading wire:target="add" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </button>
@else
    <div class="flex flex-col gap-3 sm:flex-row sm:items-stretch">
        {{-- Quantity --}}
        <div class="flex h-12 items-center rounded-lg border border-ink-300 bg-white" x-data>
            <button
                type="button"
                wire:click="$set('quantity', Math.max(1, quantity - 1))"
                class="flex size-12 items-center justify-center text-ink-500 transition hover:text-ink-900 disabled:opacity-40"
                @disabled($quantity <= 1)
                aria-label="{{ __('hanbell.common.remove') }}"
            >
                <x-heroicon-m-minus class="size-4" />
            </button>

            <label for="add-qty" class="sr-only">{{ __('hanbell.common.quantity') }}</label>
            <input
                id="add-qty"
                type="number"
                min="1"
                wire:model.live="quantity"
                class="h-12 w-12 border-0 bg-transparent p-0 text-center text-sm font-bold text-ink-900 focus:outline-none focus:ring-0"
            >

            <button
                type="button"
                wire:click="$set('quantity', quantity + 1)"
                class="flex size-12 items-center justify-center text-ink-500 transition hover:text-ink-900"
                aria-label="{{ __('hanbell.common.add') }}"
            >
                <x-heroicon-m-plus class="size-4" />
            </button>
        </div>

        <x-ui.button
            type="button"
            wire:click="add"
            variant="primary"
            size="lg"
            loading="add"
            class="flex-1"
            :disabled="! $inStock"
        >
            <span wire:loading.remove wire:target="add" class="flex items-center gap-2">
                <x-heroicon-o-shopping-bag class="size-5" />
                {{ $inStock ? __('hanbell.product.add_to_bag') : __('hanbell.product.out_of_stock') }}
            </span>

            <span wire:loading wire:target="add">{{ __('hanbell.product.adding') }}</span>
        </x-ui.button>
    </div>
@endif
