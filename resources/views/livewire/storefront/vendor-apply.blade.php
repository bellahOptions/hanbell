<div class="hb-container py-8">
    @if ($existing)
        {{-- Already applied: show the status rather than the form again, so a
             brand is never invited to apply twice. --}}
        <div class="mx-auto max-w-2xl">
            <x-ui.card>
                <h1 class="text-xl font-bold tracking-tight text-ink-950">{{ __('hanbell.vendor.application_status') }}</h1>

                <div class="mt-4 flex items-center gap-3">
                    <x-ui.badge :class="$existing->status->badgeClasses()" size="lg">{{ $existing->status->label() }}</x-ui.badge>
                    <span class="text-sm font-semibold text-ink-800">{{ $existing->name }}</span>
                </div>

                <p class="mt-4 text-sm text-ink-600">
                    @switch($existing->status->value)
                        @case('pending')
                            {{ __('hanbell.vendor.pending_hint') }}
                            @break
                        @case('approved')
                            {{ __('hanbell.vendor.status_approved') }}
                            @break
                        @case('rejected')
                            {{ __('hanbell.vendor.rejected_hint') }}
                            @if ($existing->status_reason)
                                <span class="mt-2 block rounded-lg bg-danger-50 p-3 text-danger-800">{{ $existing->status_reason }}</span>
                            @endif
                            @break
                        @default
                            {{ __('hanbell.vendor.suspended_hint') }}
                    @endswitch
                </p>

                @if ($existing->isApproved())
                    <x-ui.button :href="route('vendor.dashboard')" variant="primary" class="mt-5">
                        {{ __('hanbell.admin.dashboard') }}
                    </x-ui.button>
                @endif

                <x-ui.button :href="route('storefront.home')" variant="ghost" class="mt-2 block">
                    {{ __('hanbell.error.go_home') }}
                </x-ui.button>
            </x-ui.card>
        </div>
    @else
        {{-- Pitch --}}
        <div class="mx-auto max-w-3xl text-center">
            <p class="hb-eyebrow mb-3 text-brand-600">{{ __('hanbell.legal.nigerian_made') }}</p>
            <h1 class="text-3xl font-extrabold tracking-tight text-ink-950 sm:text-4xl">{{ __('hanbell.vendor.apply_title') }}</h1>
            <p class="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-ink-600 sm:text-base">{{ __('hanbell.vendor.apply_intro') }}</p>
        </div>

        <div class="mx-auto mt-10 grid max-w-4xl gap-4 sm:grid-cols-2">
            @foreach ([
                ['icon' => 'globe-alt', 'title' => __('hanbell.vendor.benefit_reach')],
                ['icon' => 'banknotes', 'title' => __('hanbell.vendor.benefit_fair')],
                ['icon' => 'book-open', 'title' => __('hanbell.vendor.benefit_story')],
                ['icon' => 'receipt-percent', 'title' => __('hanbell.vendor.benefit_payout')],
            ] as $benefit)
                <div class="flex items-start gap-3 rounded-xl border border-ink-200 bg-white p-4">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                        <x-dynamic-component :component="'heroicon-o-'.$benefit['icon']" class="size-4.5" />
                    </span>
                    <p class="text-sm leading-relaxed text-ink-700">{{ $benefit['title'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Application --}}
        <div class="mx-auto mt-10 max-w-3xl">
            <x-ui.card>
                <h2 class="text-lg font-bold tracking-tight text-ink-950">{{ __('hanbell.vendor.apply_form_title') }}</h2>
                <p class="mt-1 text-sm text-ink-500">{{ __('hanbell.vendor.apply_form_hint') }}</p>

                <form wire:submit="submit" class="mt-6 space-y-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.input wire:model="name" name="name" :label="__('hanbell.vendor.business_name')" required />
                        <x-ui.input wire:model="legal_name" name="legal_name" :label="__('hanbell.vendor.legal_name')" />
                        <x-ui.input wire:model="email" name="email" type="email" :label="__('hanbell.common.email')" required />
                        <x-ui.input wire:model="phone" name="phone" type="tel" :label="__('hanbell.common.phone')" required />
                        <x-ui.input wire:model="city" name="city" :label="__('hanbell.vendor.city')" required />
                        <x-ui.input wire:model="state" name="state" :label="__('hanbell.vendor.state')" required />
                        <x-ui.input wire:model="website" name="website" :label="__('hanbell.vendor.website')" class="sm:col-span-2" />
                    </div>

                    <x-ui.textarea
                        wire:model="description"
                        name="description"
                        :label="__('hanbell.vendor.about_brand')"
                        :hint="__('hanbell.vendor.about_brand_hint')"
                        :rows="4"
                        required
                    />

                    <x-ui.textarea
                        wire:model="story"
                        name="story"
                        :label="__('hanbell.vendor.brand_story')"
                        :hint="__('hanbell.vendor.brand_story_hint')"
                        :rows="4"
                    />

                    <div>
                        <label for="logo" class="mb-1.5 block text-sm font-medium text-ink-800">{{ __('hanbell.vendor.logo') }}</label>
                        <input
                            id="logo"
                            type="file"
                            wire:model="logo"
                            accept="image/*"
                            class="block w-full cursor-pointer rounded-lg border border-ink-300 text-sm text-ink-600 file:mr-3 file:cursor-pointer file:rounded-l-lg file:border-0 file:bg-ink-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-ink-700 hover:file:bg-ink-200"
                        >
                        <p class="mt-1.5 text-sm text-ink-500">{{ __('hanbell.vendor.logo_hint') }}</p>

                        @error('logo')
                            <p class="mt-1.5 text-sm text-danger-600">{{ $message }}</p>
                        @enderror

                        <div wire:loading wire:target="logo" class="mt-2 text-xs text-ink-500">{{ __('hanbell.common.loading') }}</div>
                    </div>

                    <x-ui.button type="submit" variant="primary" size="lg" block loading="submit">
                        <span wire:loading.remove wire:target="submit">{{ __('hanbell.vendor.submit_application') }}</span>
                        <span wire:loading wire:target="submit">{{ __('hanbell.common.loading') }}</span>
                    </x-ui.button>
                </form>
            </x-ui.card>
        </div>
    @endif
</div>
