@php
    use App\Enums\TwoFactorMethod;
@endphp

<div>
    <span class="mb-5 flex size-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
        <x-heroicon-o-shield-check class="size-6" />
    </span>

    <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.auth.two_factor.title') }}</h1>
    <p class="mt-1.5 text-sm text-ink-500">{{ __('hanbell.auth.two_factor.subtitle') }}</p>

    @if ($method === TwoFactorMethod::Eotp && ! $usingRecovery)
        <x-ui.alert variant="info" class="mt-5">
            {{ __('hanbell.auth.two_factor.eotp_sent', ['email' => $user?->email ?? '']) }}
        </x-ui.alert>
    @endif

    <form wire:submit="verify" class="mt-6 space-y-4">
        @if ($usingRecovery)
            <x-ui.input
                wire:model="recoveryCode"
                name="recoveryCode"
                :label="__('hanbell.auth.two_factor.recovery_label')"
                autocomplete="one-time-code"
                required
                autofocus
            />
        @else
            <x-ui.input
                wire:model="code"
                name="code"
                :label="$method === TwoFactorMethod::Totp
                    ? __('hanbell.auth.two_factor.totp_label')
                    : __('hanbell.auth.two_factor.eotp_label')"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                required
                autofocus
            />
        @endif

        <x-ui.button type="submit" variant="primary" size="lg" block loading="verify">
            <span wire:loading.remove wire:target="verify">{{ __('hanbell.auth.two_factor.verify') }}</span>
            <span wire:loading wire:target="verify">{{ __('hanbell.common.loading') }}</span>
        </x-ui.button>
    </form>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm">
        @if ($usingRecovery)
            <button type="button" wire:click="useApp" class="font-semibold text-brand-700 hover:text-brand-800">
                {{ __('hanbell.auth.two_factor.use_app') }}
            </button>
        @else
            <button type="button" wire:click="useRecovery" class="font-semibold text-brand-700 hover:text-brand-800">
                {{ __('hanbell.auth.two_factor.use_recovery') }}
            </button>
        @endif

        @if ($method === TwoFactorMethod::Eotp)
            <button type="button" wire:click="sendEmailCode" class="font-medium text-ink-500 underline underline-offset-2 hover:text-ink-800">
                {{ __('hanbell.auth.two_factor.resend') }}
            </button>
        @endif
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-8 border-t border-ink-100 pt-5">
        @csrf
        <button type="submit" class="text-sm text-ink-500 underline underline-offset-2 hover:text-ink-800">
            {{ __('hanbell.common.cancel') }}
        </button>
    </form>
</div>
