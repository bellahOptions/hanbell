<form wire:submit="subscribe" class="w-full">
    <div class="flex flex-col gap-2 sm:flex-row">
        <div class="relative flex-1">
            <label for="newsletter-email" class="sr-only">{{ __('hanbell.common.email') }}</label>

            <x-heroicon-o-envelope class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-ink-500" />

            <input
                id="newsletter-email"
                type="email"
                wire:model="email"
                required
                autocomplete="email"
                placeholder="{{ __('hanbell.newsletter.placeholder') }}"
                class="h-12 w-full rounded-lg border border-white/15 bg-white/5 pl-10 pr-4 text-sm text-white placeholder:text-ink-500 transition focus:border-accent-300 focus:bg-white/10 focus:outline-none focus:ring-2 focus:ring-accent-300/30"
            >
        </div>

        <x-ui.button
            type="submit"
            variant="accent"
            size="lg"
            loading="subscribe"
            class="shrink-0"
        >
            <span wire:loading.remove wire:target="subscribe">{{ __('hanbell.newsletter.subscribe') }}</span>
            <span wire:loading wire:target="subscribe">{{ __('hanbell.common.loading') }}</span>
        </x-ui.button>
    </div>

    @error('email')
        <p class="mt-2 flex items-center gap-1.5 text-xs text-danger-400">
            <x-heroicon-o-exclamation-circle class="size-3.5 shrink-0" />
            {{ $message }}
        </p>
    @enderror

    <p class="mt-2.5 text-xs text-ink-500">{{ __('hanbell.newsletter.privacy_hint') }}</p>
</form>
