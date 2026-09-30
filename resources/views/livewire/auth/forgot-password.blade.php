<div>
    <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.auth.forgot_title') }}</h1>
    <p class="mt-1.5 text-sm text-ink-500">{{ __('hanbell.auth.forgot_subtitle') }}</p>

    <form wire:submit="send" class="mt-7 space-y-4">
        <x-ui.input
            wire:model="email"
            name="email"
            type="email"
            :label="__('hanbell.common.email')"
            icon="envelope"
            required
            autofocus
            autocomplete="email"
        />

        <x-ui.button type="submit" variant="primary" size="lg" block loading="send">
            <span wire:loading.remove wire:target="send">{{ __('hanbell.auth.send_reset_link') }}</span>
            <span wire:loading wire:target="send">{{ __('hanbell.common.loading') }}</span>
        </x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-500">
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-700 hover:text-brand-800">
            {{ __('hanbell.auth.sign_in') }}
        </a>
    </p>
</div>
