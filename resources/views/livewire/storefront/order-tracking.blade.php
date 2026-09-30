<div class="hb-container py-10 sm:py-14">
    <div class="mx-auto max-w-lg">
        <div class="text-center">
            <span class="mx-auto mb-5 flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                <x-heroicon-o-truck class="size-7" />
            </span>

            <h1 class="text-2xl font-bold tracking-tight text-ink-950 sm:text-3xl">
                {{ __('hanbell.order.track_order') }}
            </h1>
            <p class="mt-2 text-sm text-ink-500">{{ __('hanbell.order.track_hint') }}</p>
        </div>

        <x-ui.card class="mt-7">
            <form wire:submit="search" class="space-y-4">
                <x-ui.input
                    wire:model="number"
                    name="number"
                    :label="__('hanbell.order.number')"
                    placeholder="HB-2025-000123"
                    icon="hashtag"
                    required
                />

                <x-ui.input
                    wire:model="email"
                    name="email"
                    type="email"
                    :label="__('hanbell.common.email')"
                    icon="envelope"
                    required
                    autocomplete="email"
                />

                <x-ui.button type="submit" variant="primary" size="lg" block loading="search">
                    <span wire:loading.remove wire:target="search">{{ __('hanbell.order.track_submit') }}</span>
                    <span wire:loading wire:target="search">{{ __('hanbell.common.loading') }}</span>
                </x-ui.button>
            </form>

            @if ($searched && ! $errors->any())
                <x-ui.alert variant="warning" class="mt-4">
                    {{ __('hanbell.order.not_found') }}
                </x-ui.alert>
            @endif
        </x-ui.card>

        <p class="mt-6 text-center text-xs text-ink-400">
            {{ __('hanbell.order.need_help') }}
            <a href="{{ route('storefront.contact') }}" wire:navigate class="font-semibold text-brand-700 underline underline-offset-2">
                {{ __('hanbell.order.contact_support') }}
            </a>
        </p>
    </div>
</div>
