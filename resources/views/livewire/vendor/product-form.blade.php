<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink-950">
            {{ $product ? __('hanbell.common.edit') : __('hanbell.common.create') }}
        </h1>
        <p class="mt-1 text-sm text-ink-500">
            Saved products go into the moderation queue — a brand cannot publish straight to the storefront.
        </p>
    </div>

    <form wire:submit="save" class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <x-admin.panel :title="__('hanbell.product.details_tab')">
                <div class="space-y-4">
                    <x-ui.input wire:model="name" name="name" :label="__('hanbell.product.product')" required />
                    <x-ui.textarea wire:model="summary" name="summary" :label="__('hanbell.product.description')" :rows="2" />
                    <x-ui.textarea wire:model="description" name="description" label="Full description" :rows="8" />
                </div>
            </x-admin.panel>

            <x-admin.panel :title="__('hanbell.vendor.logo')" description="The first image becomes the primary one.">
                @if ($product && $product->media->isNotEmpty())
                    <div class="mb-4 flex flex-wrap gap-3">
                        @foreach ($product->media as $media)
                            <div class="hb-frame size-20 rounded-lg">
                                <img src="{{ $media->url() }}" alt="" class="object-cover">
                            </div>
                        @endforeach
                    </div>
                @endif

                <input
                    type="file"
                    wire:model="images"
                    multiple
                    accept="image/*"
                    class="block w-full cursor-pointer rounded-lg border border-ink-300 text-sm file:mr-3 file:cursor-pointer file:rounded-l-lg file:border-0 file:bg-ink-100 file:px-3 file:py-2 file:text-sm file:font-semibold"
                >

                <div wire:loading wire:target="images" class="mt-2 text-xs text-ink-500">{{ __('hanbell.common.loading') }}</div>

                @error('images.*')
                    <p class="mt-2 text-xs text-danger-600">{{ $message }}</p>
                @enderror
            </x-admin.panel>
        </div>

        <div class="space-y-5">
            <x-admin.panel :title="__('hanbell.common.price')">
                <div class="space-y-4">
                    <x-ui.input wire:model="price" name="price" type="number" step="0.01" :label="__('hanbell.common.price')" suffix="NGN" required />
                    <x-ui.input wire:model="compare_at_price" name="compare_at_price" type="number" step="0.01" label="Was price" suffix="NGN" />
                </div>
            </x-admin.panel>

            <x-admin.panel :title="__('hanbell.admin.inventory')">
                <x-ui.input wire:model="stock" name="stock" type="number" label="Units on hand" />
            </x-admin.panel>

            <x-admin.panel :title="__('hanbell.nav.categories')">
                <x-ui.select wire:model="category_id" name="category_id" :label="__('hanbell.nav.categories')" :options="$categories" :placeholder="__('hanbell.common.none')" />
            </x-admin.panel>

            <x-admin.panel :title="__('hanbell.product.details_tab')">
                <div class="space-y-4">
                    <x-ui.select wire:model="gender" name="gender" :label="__('hanbell.shop.gender')" :options="$genders" />
                    <x-ui.input wire:model="material" name="material" :label="__('hanbell.product.material')" />
                    <x-ui.input wire:model="made_in_city" name="made_in_city" :label="__('hanbell.product.made_in')" hint="e.g. Aba, Kano, Lagos" />
                    <x-ui.input wire:model="care_instructions" name="care_instructions" :label="__('hanbell.product.care_instructions')" />
                </div>
            </x-admin.panel>

            <x-ui.button type="submit" variant="primary" size="lg" block loading="save">
                <span wire:loading.remove wire:target="save">{{ __('hanbell.common.save_changes') }}</span>
                <span wire:loading wire:target="save">{{ __('hanbell.common.loading') }}</span>
            </x-ui.button>

            <x-ui.button :href="route('vendor.products.index')" variant="ghost" block>
                {{ __('hanbell.common.cancel') }}
            </x-ui.button>
        </div>
    </form>
</div>
