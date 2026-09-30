@if (! auth()->user()?->isAdmin())
    <button
        type="button"
        wire:click="toggle"
        wire:loading.attr="disabled"
        wire:target="toggle"
        @class([
            'absolute right-2.5 top-2.5 z-20 flex size-9 items-center justify-center rounded-full backdrop-blur-sm transition-all duration-200',
            'bg-white/90 text-ink-500 hover:bg-white hover:text-danger-500 shadow-sm' => ! $saved,
            'bg-danger-500 text-white shadow-md' => $saved,
        ])
        aria-pressed="{{ $saved ? 'true' : 'false' }}"
        aria-label="{{ $saved ? __('hanbell.product.remove_from_wishlist') : __('hanbell.product.add_to_wishlist') }}"
        title="{{ $saved ? __('hanbell.product.remove_from_wishlist') : __('hanbell.product.add_to_wishlist') }}"
    >
        <span wire:loading.remove wire:target="toggle">
            <x-heroicon-s-heart @class(['size-4.5', 'hidden' => ! $saved]) />
            <x-heroicon-o-heart @class(['size-4.5', 'hidden' => $saved]) />
        </span>

        <svg wire:loading wire:target="toggle" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </button>
@endif
