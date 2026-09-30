<div>
    <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.auth.reset_title') }}</h1>
    <p class="mt-1.5 text-sm text-ink-500">{{ __('hanbell.auth.reset_subtitle') }}</p>

    <form wire:submit="resetPassword" class="mt-7 space-y-4">
        <x-ui.input
            wire:model="email"
            name="email"
            type="email"
            :label="__('hanbell.common.email')"
            icon="envelope"
            required
            autocomplete="email"
        />

        <x-ui.input
            wire:model="password"
            name="password"
            type="password"
            :label="__('hanbell.account.new_password')"
            icon="lock-closed"
            required
            autocomplete="new-password"
            :hint="__('hanbell.auth.password_requirements')"
        />

        <x-ui.input
            wire:model="password_confirmation"
            name="password_confirmation"
            type="password"
            :label="__('hanbell.auth.confirm_password')"
            icon="lock-closed"
            required
            autocomplete="new-password"
        />

        <x-ui.button type="submit" variant="primary" size="lg" block loading="resetPassword">
            <span wire:loading.remove wire:target="resetPassword">{{ __('hanbell.auth.reset_password') }}</span>
            <span wire:loading wire:target="resetPassword">{{ __('hanbell.common.loading') }}</span>
        </x-ui.button>
    </form>
</div>
