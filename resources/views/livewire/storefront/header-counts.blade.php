@if (! $isAdmin)
    <a
        href="{{ route('storefront.cart') }}"
        wire:navigate
        class="relative rounded-lg p-2.5 text-ink-700 transition hover:bg-ink-100"
        aria-label="{{ __('hanbell.nav.bag') }}"
    >
        <x-heroicon-o-shopping-bag class="size-5" />

        @if ($cartCount > 0)
            <span
                class="absolute -right-0.5 -top-0.5 flex min-w-4.5 items-center justify-center rounded-full bg-accent-300 px-1 text-[10px] font-extrabold leading-4 text-ink-950 ring-2 ring-white"
                aria-hidden="true"
            >
                {{ $cartCount > 99 ? '99+' : $cartCount }}
            </span>
            <span class="sr-only">{{ trans_choice('hanbell.cart.items_in_bag', $cartCount, ['count' => $cartCount]) }}</span>
        @endif
    </a>
@endif
