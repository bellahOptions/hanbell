<div>
    <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.auth.login_title') }}</h1>
    <p class="mt-1.5 text-sm text-ink-500">{{ __('hanbell.auth.login_subtitle') }}</p>

    <form wire:submit="login" class="mt-7 space-y-4">
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

        <div>
            <x-ui.input
                wire:model="password"
                name="password"
                type="password"
                :label="__('hanbell.common.password')"
                icon="lock-closed"
                required
                autocomplete="current-password"
            />

            <div class="mt-2.5 flex items-center justify-between gap-3">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-600">
                    <input type="checkbox" wire:model="remember" class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600/30">
                    {{ __('hanbell.auth.remember_me') }}
                </label>

                <a href="{{ route('password.request') }}" wire:navigate class="text-sm font-semibold text-brand-700 hover:text-brand-800">
                    {{ __('hanbell.auth.forgot_password') }}
                </a>
            </div>
        </div>

        <x-ui.button type="submit" variant="primary" size="lg" block loading="login">
            <span wire:loading.remove wire:target="login">{{ __('hanbell.auth.sign_in') }}</span>
            <span wire:loading wire:target="login">{{ __('hanbell.common.loading') }}</span>
        </x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-500">
        {{ __('hanbell.auth.no_account') }}
        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-brand-700 hover:text-brand-800">
            {{ __('hanbell.auth.sign_up') }}
        </a>
    </p>

    {{-- Demo credentials, shown only while the demo dataset is present. --}}
    @if (config('hanbell.demo.enabled') && app()->environment('local'))
        <div class="mt-8 rounded-xl border border-ink-200 bg-ink-50 p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-ink-500">Demo accounts</p>
            <ul class="mt-2 space-y-1 text-xs text-ink-600">
                <li><span class="font-semibold">Admin</span> — admin@hanbellshop.demo / password</li>
                <li><span class="font-semibold">Customer</span> — customer@hanbellshop.demo / password</li>
                <li><span class="font-semibold">2FA on</span> — secured@hanbellshop.demo / password</li>
            </ul>
        </div>
    @endif
</div>
