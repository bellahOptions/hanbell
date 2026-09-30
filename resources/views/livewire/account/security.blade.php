<div class="hb-container py-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink-950 sm:text-3xl">{{ __('hanbell.account.security_title') }}</h1>

    @include('partials.account-nav')

    <div class="mt-6 grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            {{-- Two-factor --}}
            <x-admin.panel :title="__('hanbell.account.two_factor')" :description="__('hanbell.account.security_hint')">
                @if ($enabled)
                    <div class="flex flex-wrap items-center gap-3 rounded-xl border border-brand-600/25 bg-brand-50 p-4">
                        <x-heroicon-s-shield-check class="size-5 shrink-0 text-brand-600" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-brand-900">{{ __('hanbell.account.two_factor_enabled') }}</p>
                            <p class="text-xs text-brand-800/80">{{ $user->twoFactorMethod()->label() }}</p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($user->twoFactorMethod()->value === 'totp')
                            <x-ui.button wire:click="regenerateRecoveryCodes" variant="outline" size="sm">
                                {{ __('hanbell.account.regenerate_codes') }}
                            </x-ui.button>
                        @endif

                        <x-ui.button wire:click="disable" variant="ghost" size="sm">
                            {{ __('hanbell.account.disable_two_factor') }}
                        </x-ui.button>
                    </div>

                    @if ($showRecoveryCodes && $recoveryCodes !== [])
                        <div class="mt-5 rounded-xl border border-warning-500/30 bg-warning-50 p-4">
                            <p class="text-sm font-bold text-warning-900">{{ __('hanbell.account.recovery_codes') }}</p>
                            <p class="mt-1 text-xs text-warning-800">{{ __('hanbell.account.recovery_codes_warning') }}</p>

                            <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @foreach ($recoveryCodes as $recoveryCode)
                                    <code class="rounded-md bg-white px-2 py-1.5 text-center font-mono text-xs font-semibold text-ink-900">{{ $recoveryCode }}</code>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <p class="text-sm text-ink-600">{{ __('hanbell.account.two_factor_disabled_hint') }}</p>

                    {{-- TOTP enrolment --}}
                    @if ($setupQr)
                        <div class="mt-5 rounded-xl border border-ink-200 p-5">
                            <p class="text-sm font-bold text-ink-950">{{ __('hanbell.account.setup_app') }}</p>
                            <p class="mt-1 text-xs text-ink-500">{{ __('hanbell.account.setup_app_hint') }}</p>

                            <div class="mt-4 flex flex-wrap items-start gap-5">
                                <div class="rounded-xl border border-ink-200 bg-white p-3">
                                    {{-- Rendered locally: the provisioning URI contains the
                                         shared secret, so it must never be sent to a
                                         third-party QR service. --}}
                                    {!! $setupQr !!}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-ink-400">
                                        {{ __('hanbell.account.manual_entry') }}
                                    </p>
                                    <code class="mt-1.5 block break-all rounded-lg bg-ink-100 px-3 py-2 font-mono text-xs font-semibold text-ink-900">
                                        {{ $setupSecret }}
                                    </code>

                                    <form wire:submit="confirmTotp" class="mt-4 space-y-3">
                                        <x-ui.input
                                            wire:model="confirmCode"
                                            name="confirmCode"
                                            :label="__('hanbell.account.confirm_code')"
                                            inputmode="numeric"
                                            maxlength="6"
                                            required
                                        />

                                        <x-ui.button type="submit" variant="primary" block loading="confirmTotp">
                                            <span wire:loading.remove wire:target="confirmTotp">{{ __('hanbell.account.confirm_enable') }}</span>
                                            <span wire:loading wire:target="confirmTotp">{{ __('hanbell.common.loading') }}</span>
                                        </x-ui.button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <button
                            type="button"
                            wire:click="beginTotpSetup"
                            class="flex flex-col items-start gap-2 rounded-xl border border-ink-200 p-4 text-left transition hover:border-brand-600/40 hover:bg-brand-50/40"
                        >
                            <span class="flex size-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                <x-heroicon-o-device-phone-mobile class="size-4.5" />
                            </span>
                            <span class="text-sm font-bold text-ink-950">{{ __('hanbell.account.method_app') }}</span>
                            <span class="text-xs leading-relaxed text-ink-500">{{ __('hanbell.account.method_app_hint') }}</span>
                        </button>

                        <button
                            type="button"
                            wire:click="enableEotp"
                            class="flex flex-col items-start gap-2 rounded-xl border border-ink-200 p-4 text-left transition hover:border-brand-600/40 hover:bg-brand-50/40"
                        >
                            <span class="flex size-9 items-center justify-center rounded-lg bg-info-50 text-info-600">
                                <x-heroicon-o-envelope class="size-4.5" />
                            </span>
                            <span class="text-sm font-bold text-ink-950">{{ __('hanbell.account.method_email') }}</span>
                            <span class="text-xs leading-relaxed text-ink-500">{{ __('hanbell.account.method_email_hint') }}</span>
                        </button>
                    </div>
                @endif
            </x-admin.panel>

            {{-- Sign-in activity --}}
            <x-admin.panel :title="__('hanbell.account.login_activity')" padding="none">
                @if ($loginActivity->isEmpty())
                    <p class="p-5 text-sm text-ink-500">{{ __('hanbell.account.no_login_activity') }}</p>
                @else
                    <ul class="divide-y divide-ink-100">
                        @foreach ($loginActivity as $attempt)
                            <li class="flex items-center justify-between gap-4 px-5 py-3">
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-ink-800">
                                        {{ $attempt->successful ? __('hanbell.auth.signed_in', ['name' => '']) : __('hanbell.auth.invalid_credentials') }}
                                    </span>
                                    <span class="block text-xs text-ink-500">
                                        {{ $attempt->ip_address }} · {{ \Illuminate\Support\Str::limit((string) $attempt->user_agent, 50) }}
                                    </span>
                                </span>

                                <span class="shrink-0 text-right">
                                    <x-ui.badge :variant="$attempt->successful ? 'brand' : 'danger'" size="xs">
                                        {{ $attempt->successful ? __('hanbell.common.yes') : __('hanbell.common.no') }}
                                    </x-ui.badge>
                                    <span class="mt-1 block text-[11px] text-ink-400">{{ $attempt->created_at?->diffForHumans() }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.panel>
        </div>

        {{-- Audit trail --}}
        <div>
            <x-admin.panel :title="__('hanbell.account.audit_log')" padding="none">
                @if ($auditTrail->isEmpty())
                    <p class="p-5 text-sm text-ink-500">{{ __('hanbell.common.no_items') }}</p>
                @else
                    <ul class="divide-y divide-ink-100">
                        @foreach ($auditTrail as $log)
                            <li class="px-5 py-3">
                                <p class="text-xs font-semibold text-ink-800">{{ $log->label() }}</p>
                                <p class="mt-0.5 text-[11px] text-ink-400">
                                    {{ $log->created_at?->diffForHumans() }} · {{ $log->ip_address }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.panel>
        </div>
    </div>
</div>
