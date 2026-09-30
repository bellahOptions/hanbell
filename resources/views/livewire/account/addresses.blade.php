<div class="hb-container py-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink-950 sm:text-3xl">{{ __('hanbell.account.addresses') }}</h1>

    @include('partials.account-nav')

    <div class="mt-6 grid gap-5 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            @if ($addresses->isEmpty())
                <x-ui.card padding="none">
                    <x-ui.empty-state icon="map-pin" :title="__('hanbell.account.no_addresses')" :message="__('hanbell.account.addresses')" />
                </x-ui.card>
            @else
                @foreach ($addresses as $address)
                    <x-ui.card>
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-center gap-2 text-sm font-bold text-ink-950">
                                    {{ $address->recipient_name }}

                                    @if ($address->label)
                                        <x-ui.badge variant="neutral" size="xs">{{ $address->label }}</x-ui.badge>
                                    @endif

                                    @if ($address->is_default)
                                        <x-ui.badge variant="brand" size="xs">{{ __('hanbell.account.default_address') }}</x-ui.badge>
                                    @endif
                                </p>

                                <p class="mt-1.5 text-sm leading-relaxed text-ink-600">{{ $address->singleLine() }}</p>
                                <p class="mt-1 text-xs text-ink-500">{{ $address->phone }}</p>
                            </div>

                            <div class="flex shrink-0 items-center gap-1">
                                @unless ($address->is_default)
                                    <button
                                        type="button"
                                        wire:click="makeDefault({{ $address->id }})"
                                        class="rounded-md px-2 py-1.5 text-xs font-semibold text-brand-700 transition hover:bg-brand-50"
                                    >
                                        {{ __('hanbell.account.set_default') }}
                                    </button>
                                @endunless

                                <button
                                    type="button"
                                    wire:click="edit({{ $address->id }})"
                                    class="rounded-md p-1.5 text-ink-400 transition hover:bg-ink-100 hover:text-ink-700"
                                    aria-label="{{ __('hanbell.common.edit') }}"
                                >
                                    <x-heroicon-o-pencil-square class="size-4" />
                                </button>

                                <button
                                    type="button"
                                    wire:click="delete({{ $address->id }})"
                                    wire:confirm="{{ __('hanbell.admin.confirm_delete') }}"
                                    class="rounded-md p-1.5 text-ink-400 transition hover:bg-danger-50 hover:text-danger-600"
                                    aria-label="{{ __('hanbell.common.delete') }}"
                                >
                                    <x-heroicon-o-trash class="size-4" />
                                </button>
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            @endif
        </div>

        {{-- Add / edit form --}}
        <div>
            @if (! $showForm)
                <x-ui.button wire:click="create" variant="primary" block>
                    <x-heroicon-o-plus class="size-4" />
                    {{ __('hanbell.account.add_address') }}
                </x-ui.button>
            @else
                <x-ui.card>
                    <h2 class="text-sm font-bold text-ink-950">
                        {{ $editingId ? __('hanbell.account.edit_address') : __('hanbell.account.add_address') }}
                    </h2>

                    <form wire:submit="save" class="mt-4 space-y-4">
                        <x-ui.input wire:model="recipient_name" name="recipient_name" :label="__('hanbell.checkout.fields.recipient_name')" required />
                        <x-ui.input wire:model="phone" name="phone" type="tel" :label="__('hanbell.common.phone')" required />
                        <x-ui.input wire:model="label" name="label" :label="__('hanbell.checkout.fields.label')" :hint="__('hanbell.checkout.fields.label_hint')" />
                        <x-ui.input wire:model="line1" name="line1" :label="__('hanbell.checkout.fields.line1')" required />
                        <x-ui.input wire:model="line2" name="line2" :label="__('hanbell.checkout.fields.line2')" />
                        <x-ui.input wire:model="city" name="city" :label="__('hanbell.checkout.fields.city')" required />
                        <x-ui.input wire:model="state" name="state" :label="__('hanbell.checkout.fields.state')" required />
                        <x-ui.input wire:model="postal_code" name="postal_code" :label="__('hanbell.checkout.fields.postal_code')" />

                        <div class="flex gap-2">
                            <x-ui.button type="submit" variant="primary" block loading="save">
                                <span wire:loading.remove wire:target="save">{{ __('hanbell.common.save') }}</span>
                                <span wire:loading wire:target="save">{{ __('hanbell.common.loading') }}</span>
                            </x-ui.button>

                            <x-ui.button type="button" wire:click="cancel" variant="ghost">
                                {{ __('hanbell.common.cancel') }}
                            </x-ui.button>
                        </div>
                    </form>
                </x-ui.card>
            @endif
        </div>
    </div>
</div>
