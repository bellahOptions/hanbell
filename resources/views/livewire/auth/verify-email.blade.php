<div>
    <span class="mb-5 flex size-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
        <x-heroicon-o-envelope-open class="size-6" />
    </span>

    <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.auth.verify_title') }}</h1>
    <p class="mt-1.5 text-sm text-ink-500">
        {{ __('hanbell.auth.verify_subtitle', ['email' => $email]) }}
    </p>
    <p class="mt-3 text-sm text-ink-600">{{ __('hanbell.auth.verify_hint') }}</p>

    <div class="mt-7 flex flex-wrap gap-2">
        <x-ui.button wire:click="resend" variant="primary" loading="resend">
            <span wire:loading.remove wire:target="resend">{{ __('hanbell.auth.verify_resend') }}</span>
            <span wire:loading wire:target="resend">{{ __('hanbell.common.loading') }}</span>
        </x-ui.button>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.button type="submit" variant="ghost">{{ __('hanbell.common.logout') }}</x-ui.button>
        </form>
    </div>

    <div class="mt-8 border-t border-ink-100 pt-6">
        <p class="text-sm font-semibold text-ink-800">{{ __('hanbell.auth.verify_use_code') }}</p>

        <form wire:submit="verifyWithCode" class="mt-3 space-y-3">
            <x-ui.input
                wire:model="code"
                name="code"
                :label="__('hanbell.auth.verify_code_label')"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                required
            />

            <x-ui.button type="submit" variant="outline" block loading="verifyWithCode">
                <span wire:loading.remove wire:target="verifyWithCode">{{ __('hanbell.auth.verify_submit') }}</span>
                <span wire:loading wire:target="verifyWithCode">{{ __('hanbell.common.loading') }}</span>
            </x-ui.button>
        </form>
    </div>
</div>
