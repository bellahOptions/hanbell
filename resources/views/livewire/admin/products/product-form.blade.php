<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink-950">
            {{ $product ? __('hanbell.common.edit') : __('hanbell.common.create') }}
        </h1>
        <p class="mt-1 text-sm text-ink-500">{{ $product?->name ?? 'New product' }}</p>
    </div>

    <form wire:submit="save" class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <x-admin.panel :title="__('hanbell.product.details_tab')">
                <div class="space-y-4">
                    <x-ui.input wire:model="name" name="name" :label="__('hanbell.product.product')" required />
                    <x-ui.input wire:model="sku" name="sku" :label="__('hanbell.product.sku')" />
                    <x-ui.textarea wire:model="summary" name="summary" :label="__('hanbell.product.description')" :rows="2" />
                    <x-ui.textarea wire:model="description" name="description" :label="__('hanbell.product.details_tab')" :rows="8" />
                </div>
            </x-admin.panel>

            <x-admin.panel :title="__('hanbell.admin.seo')" description="How this product appears to search engines and language models.">
                <div class="space-y-4">
                    <x-ui.input wire:model="meta_title" name="meta_title" label="Meta title" />
                    <x-ui.textarea wire:model="meta_description" name="meta_description" label="Meta description" :rows="2" />

                    <x-ui.textarea
                        wire:model="llm_summary"
                        name="llm_summary"
                        label="LLM summary"
                        :rows="3"
                        hint="A factual paragraph written for language models. Published in llms.txt and used in the product's structured data."
                    />

                    <label class="flex items-center gap-2.5 text-sm text-ink-700">
                        <input type="checkbox" wire:model="is_indexable" class="size-4 rounded border-ink-300 text-brand-600">
                        Allow search engines to index this product
                    </label>
                </div>
            </x-admin.panel>
        </div>

        <div class="space-y-5">
            <x-admin.panel :title="__('hanbell.common.status')">
                <div class="space-y-4">
                    <x-ui.select wire:model="status" name="status" :label="__('hanbell.common.status')" :options="$statuses" />
                    <x-ui.select wire:model="vendor_id" name="vendor_id" :label="__('hanbell.vendor.brand')" :options="$vendors" :placeholder="__('hanbell.vendor.brand')" />
                    <x-ui.select wire:model="department_id" name="department_id" :label="__('hanbell.admin.departments')" :options="$departments" :placeholder="__('hanbell.common.none')" />
                    <x-ui.select wire:model="category_id" name="category_id" :label="__('hanbell.nav.categories')" :options="$categories" :placeholder="__('hanbell.common.none')" />
                    <x-ui.select wire:model="gender" name="gender" :label="__('hanbell.shop.gender')" :options="$genders" />
                </div>
            </x-admin.panel>

            <x-admin.panel :title="__('hanbell.common.price')">
                <div class="space-y-4">
                    <x-ui.input wire:model="price" name="price" type="number" step="0.01" :label="__('hanbell.common.price')" suffix="NGN" required />
                    <x-ui.input wire:model="compare_at_price" name="compare_at_price" type="number" step="0.01" label="Compare-at price" suffix="NGN" />
                </div>
            </x-admin.panel>

            <x-admin.panel :title="__('hanbell.product.details_tab')">
                <div class="space-y-4">
                    <x-ui.input wire:model="material" name="material" :label="__('hanbell.product.material')" />
                    <x-ui.input wire:model="made_in_city" name="made_in_city" :label="__('hanbell.product.made_in')" hint="e.g. Aba, Kano, Lagos" />
                    <x-ui.input wire:model="care_instructions" name="care_instructions" :label="__('hanbell.product.care_instructions')" />
                </div>
            </x-admin.panel>

            <x-admin.panel :title="__('hanbell.product.featured_badge')">
                <div class="space-y-3">
                    @foreach ([
                        'is_featured' => __('hanbell.product.featured_badge'),
                        'is_new_arrival' => __('hanbell.product.new_badge'),
                        'is_trending' => __('hanbell.product.trending_badge'),
                        'is_handmade' => __('hanbell.product.handmade'),
                    ] as $field => $label)
                        <label class="flex items-center gap-2.5 text-sm text-ink-700">
                            <input type="checkbox" wire:model="{{ $field }}" class="size-4 rounded border-ink-300 text-brand-600">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </x-admin.panel>

            <x-ui.button type="submit" variant="primary" size="lg" block loading="save">
                <span wire:loading.remove wire:target="save">{{ __('hanbell.common.save_changes') }}</span>
                <span wire:loading wire:target="save">{{ __('hanbell.common.loading') }}</span>
            </x-ui.button>

            <x-ui.button :href="route('admin.products.index')" variant="ghost" block>
                {{ __('hanbell.common.cancel') }}
            </x-ui.button>
        </div>
    </form>
</div>
