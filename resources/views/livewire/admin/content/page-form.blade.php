<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink-950">
                {{ $page ? __('hanbell.common.edit') : __('hanbell.common.create') }}
            </h1>
            <p class="mt-1 text-sm text-ink-500">
                @if ($page)
                    <a href="{{ $page->publicUrl() }}" target="_blank" rel="noopener" class="text-brand-700 underline underline-offset-2">
                        /pages/{{ $page->slug }}
                    </a>
                @else
                    Policy pages, about, help and contact content.
                @endif
            </p>
        </div>

        <x-ui.button :href="route('admin.pages.index')" variant="ghost" size="sm">
            {{ __('hanbell.common.back') }}
        </x-ui.button>
    </div>

    <form wire:submit="save" class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <x-admin.panel :title="__('hanbell.common.details')">
                <div class="space-y-4">
                    <x-ui.input wire:model="title" name="title" label="Title" required />
                    <x-ui.input wire:model="slug" name="slug" label="Slug" hint="Leave blank to derive it from the title." />
                    <x-ui.textarea wire:model="excerpt" name="excerpt" label="Excerpt" :rows="2" hint="Used as the meta description fallback and in llms.txt." />

                    <div>
                        <label for="content" class="mb-1.5 block text-sm font-medium text-ink-800">Content</label>
                        <textarea
                            id="content"
                            wire:model="content"
                            rows="18"
                            class="w-full rounded-lg border border-ink-300 px-3.5 py-2.5 font-mono text-sm text-ink-900 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20"
                        ></textarea>
                        <p class="mt-1.5 text-xs text-ink-500">
                            Basic HTML is allowed (paragraphs, headings, lists, links, tables). Script and iframe tags are stripped.
                        </p>
                    </div>
                </div>
            </x-admin.panel>

            <x-admin.panel :title="__('hanbell.admin.seo')">
                <div class="space-y-4">
                    <x-ui.input wire:model="meta_title" name="meta_title" label="Meta title" />
                    <x-ui.textarea wire:model="meta_description" name="meta_description" label="Meta description" :rows="2" />
                    <x-ui.textarea
                        wire:model="llm_summary"
                        name="llm_summary"
                        label="LLM summary"
                        :rows="3"
                        hint="A factual paragraph for language models. Also used as the page's structured-data description."
                    />

                    <label class="flex items-center gap-2.5 text-sm text-ink-700">
                        <input type="checkbox" wire:model="is_indexable" class="size-4 rounded border-ink-300 text-brand-600">
                        Allow search engines to index this page
                    </label>
                </div>
            </x-admin.panel>
        </div>

        <div class="space-y-5">
            <x-admin.panel :title="__('hanbell.common.status')">
                <div class="space-y-4">
                    <x-ui.select wire:model="status" name="status" :label="__('hanbell.common.status')" :options="$statuses" />

                    <x-ui.select wire:model="group" name="group" label="Group" :options="[
                        'general' => 'General',
                        'policy' => 'Policy',
                        'help' => 'Help',
                        'legal' => 'Legal',
                    ]" />

                    <x-ui.input wire:model="position" name="position" type="number" label="Sort position" />
                </div>
            </x-admin.panel>

            <x-admin.panel title="Placement">
                <div class="space-y-3">
                    <label class="flex items-center gap-2.5 text-sm text-ink-700">
                        <input type="checkbox" wire:model="show_in_footer" class="size-4 rounded border-ink-300 text-brand-600">
                        Show in the footer
                    </label>

                    <label class="flex items-center gap-2.5 text-sm text-ink-700">
                        <input type="checkbox" wire:model="show_in_header" class="size-4 rounded border-ink-300 text-brand-600">
                        Show in the header
                    </label>
                </div>
            </x-admin.panel>

            <x-ui.button type="submit" variant="primary" size="lg" block loading="save">
                <span wire:loading.remove wire:target="save">{{ __('hanbell.common.save_changes') }}</span>
                <span wire:loading wire:target="save">{{ __('hanbell.common.loading') }}</span>
            </x-ui.button>

            <x-ui.button :href="route('admin.pages.index')" variant="ghost" block>
                {{ __('hanbell.common.cancel') }}
            </x-ui.button>
        </div>
    </form>
</div>
