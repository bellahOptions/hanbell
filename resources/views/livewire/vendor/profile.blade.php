<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ $vendor?->name ?? __('hanbell.vendor.brand') }}</h1>
        <p class="mt-1 text-sm text-ink-500">{{ __('hanbell.vendor.brand_story_hint') }}</p>
    </div>

    @if ($vendor)
        <form wire:submit="save" class="grid gap-5 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                <x-admin.panel :title="__('hanbell.vendor.about_brand')">
                    <div class="space-y-4">
                        <x-ui.input wire:model="name" name="name" :label="__('hanbell.vendor.business_name')" required />
                        <x-ui.textarea wire:model="description" name="description" :label="__('hanbell.vendor.about_brand')" :rows="3" />
                        <x-ui.textarea wire:model="story" name="story" :label="__('hanbell.vendor.brand_story')" :rows="6" />
                    </div>
                </x-admin.panel>
            </div>

            <div class="space-y-5">
                <x-admin.panel :title="__('hanbell.common.details')">
                    <div class="space-y-4">
                        <x-ui.input wire:model="phone" name="phone" type="tel" :label="__('hanbell.common.phone')" />
                        <x-ui.input wire:model="whatsapp" name="whatsapp" :label="__('hanbell.footer.whatsapp')" />
                        <x-ui.input wire:model="website" name="website" :label="__('hanbell.vendor.website')" />
                        <x-ui.input wire:model="city" name="city" :label="__('hanbell.vendor.city')" />
                        <x-ui.input wire:model="state" name="state" :label="__('hanbell.vendor.state')" />
                    </div>
                </x-admin.panel>

                <x-admin.panel :title="__('hanbell.vendor.logo')">
                    @if ($vendor->logo_path)
                        <img
                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($vendor->logo_path) }}"
                            alt="{{ $vendor->name }}"
                            class="mb-3 size-20 rounded-xl object-cover"
                        >
                    @endif

                    <input
                        type="file"
                        wire:model="logo"
                        accept="image/*"
                        class="block w-full cursor-pointer rounded-lg border border-ink-300 text-sm file:mr-3 file:cursor-pointer file:rounded-l-lg file:border-0 file:bg-ink-100 file:px-3 file:py-2 file:text-sm file:font-semibold"
                    >
                    <p class="mt-1.5 text-xs text-ink-500">{{ __('hanbell.vendor.logo_hint') }}</p>

                    @error('logo')
                        <p class="mt-1.5 text-xs text-danger-600">{{ $message }}</p>
                    @enderror
                </x-admin.panel>

                <x-ui.button type="submit" variant="primary" size="lg" block loading="save">
                    <span wire:loading.remove wire:target="save">{{ __('hanbell.common.save_changes') }}</span>
                    <span wire:loading wire:target="save">{{ __('hanbell.common.loading') }}</span>
                </x-ui.button>

                @if ($vendor->isApproved())
                    <x-ui.button :href="$vendor->publicUrl()" variant="ghost" block>
                        {{ __('hanbell.admin.back_to_store') }}
                    </x-ui.button>
                @endif
            </div>
        </form>
    @endif
</div>
