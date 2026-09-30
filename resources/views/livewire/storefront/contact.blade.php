<div class="hb-container py-10 sm:py-14">
    <div class="mx-auto max-w-xl">
        <div class="text-center">
            <span class="mx-auto mb-5 flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                <x-heroicon-o-lifebuoy class="size-7" />
            </span>

            <h1 class="text-2xl font-bold tracking-tight text-ink-950 sm:text-3xl">{{ __('hanbell.nav.contact') }}</h1>
            <p class="mt-2 text-sm text-ink-500">{{ __('hanbell.brand.description') }}</p>
        </div>

        {{-- Direct routes, for anyone who would rather not fill in a form --}}
        <div class="mt-8 grid gap-3 sm:grid-cols-2">
            <a href="mailto:{{ config('hanbell.support_email') }}" class="flex items-center gap-3 rounded-xl border border-ink-200 bg-white p-4 transition hover:border-brand-600/40">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-ink-100 text-ink-500">
                    <x-heroicon-o-envelope class="size-4.5" />
                </span>
                <span class="min-w-0">
                    <span class="block text-xs font-semibold uppercase tracking-wider text-ink-400">{{ __('hanbell.common.email') }}</span>
                    <span class="clamp-1 block text-sm font-medium text-ink-800">{{ config('hanbell.support_email') }}</span>
                </span>
            </a>

            <a href="tel:{{ preg_replace('/\s+/', '', (string) config('hanbell.support_phone')) }}" class="flex items-center gap-3 rounded-xl border border-ink-200 bg-white p-4 transition hover:border-brand-600/40">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-ink-100 text-ink-500">
                    <x-heroicon-o-phone class="size-4.5" />
                </span>
                <span class="min-w-0">
                    <span class="block text-xs font-semibold uppercase tracking-wider text-ink-400">{{ __('hanbell.common.phone') }}</span>
                    <span class="clamp-1 block text-sm font-medium text-ink-800">{{ config('hanbell.support_phone') }}</span>
                </span>
            </a>
        </div>

        <x-ui.card class="mt-5">
            <form wire:submit="send" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input wire:model="name" name="name" :label="__('hanbell.common.name')" required autocomplete="name" />
                    <x-ui.input wire:model="email" name="email" type="email" :label="__('hanbell.common.email')" required autocomplete="email" />
                </div>

                <x-ui.input wire:model="subject" name="subject" :label="__('hanbell.common.details')" required />

                <x-ui.textarea wire:model="message" name="message" :label="__('hanbell.order.contact_support')" :rows="6" required />

                <x-ui.button type="submit" variant="primary" size="lg" block loading="send">
                    <span wire:loading.remove wire:target="send">{{ __('hanbell.common.confirm') }}</span>
                    <span wire:loading wire:target="send">{{ __('hanbell.common.loading') }}</span>
                </x-ui.button>
            </form>
        </x-ui.card>

        <p class="mt-6 text-center text-xs text-ink-400">
            {{ __('hanbell.footer.track_order') }} —
            <a href="{{ route('storefront.orders.track') }}" wire:navigate class="font-semibold text-brand-700 underline underline-offset-2">
                {{ __('hanbell.order.track_order') }}
            </a>
        </p>
    </div>
</div>
