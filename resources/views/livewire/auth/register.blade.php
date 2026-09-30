<div>
    <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.auth.register_title') }}</h1>
    <p class="mt-1.5 text-sm text-ink-500">{{ __('hanbell.auth.register_subtitle') }}</p>

    <form wire:submit="register" class="mt-7 space-y-4">
        <x-ui.input wire:model="name" name="name" :label="__('hanbell.common.name')" icon="user" required autofocus autocomplete="name" />
        <x-ui.input wire:model="email" name="email" type="email" :label="__('hanbell.common.email')" icon="envelope" required autocomplete="email" />
        <x-ui.input wire:model="phone" name="phone" type="tel" :label="__('hanbell.common.phone')" icon="phone" autocomplete="tel" />

        <x-ui.input
            wire:model="password"
            name="password"
            type="password"
            :label="__('hanbell.common.password')"
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

        <label class="flex cursor-pointer items-start gap-2.5 text-sm text-ink-600">
            <input type="checkbox" wire:model="terms" class="mt-0.5 size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600/30">
            <span>
                {!! __('hanbell.auth.agree_terms', [
                    'terms' => '<a href="'.route('storefront.pages.show', ['slug' => 'terms-of-service']).'" class="font-semibold text-brand-700 underline underline-offset-2">'.__('hanbell.checkout.terms').'</a>',
                    'privacy' => '<a href="'.route('storefront.pages.show', ['slug' => 'privacy-policy']).'" class="font-semibold text-brand-700 underline underline-offset-2">'.__('hanbell.checkout.privacy').'</a>',
                ]) !!}
            </span>
        </label>

        @error('terms')
            <p class="text-sm text-danger-600">{{ $message }}</p>
        @enderror

        <x-ui.button type="submit" variant="primary" size="lg" block loading="register">
            <span wire:loading.remove wire:target="register">{{ __('hanbell.auth.sign_up') }}</span>
            <span wire:loading wire:target="register">{{ __('hanbell.common.loading') }}</span>
        </x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-500">
        {{ __('hanbell.auth.have_account') }}
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-700 hover:text-brand-800">
            {{ __('hanbell.auth.sign_in') }}
        </a>
    </p>
</div>
