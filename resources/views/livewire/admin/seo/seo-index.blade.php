<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('hanbell.admin.seo_settings') }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-ink-500">
            Two audiences are configured here: search crawlers, and the language models that increasingly
            answer shopping questions. Everything below is live immediately.
        </p>
    </div>

    {{-- Discovery endpoints, so the operator can check them directly --}}
    <div class="grid gap-3 sm:grid-cols-3">
        @foreach ([
            ['label' => 'Sitemap', 'url' => $sitemapUrl, 'icon' => 'map'],
            ['label' => 'robots.txt', 'url' => $robotsUrl, 'icon' => 'document-text'],
            ['label' => 'llms.txt', 'url' => $llmsUrl, 'icon' => 'sparkles'],
        ] as $endpoint)
            <a
                href="{{ $endpoint['url'] }}"
                target="_blank"
                rel="noopener"
                class="flex items-center gap-3 rounded-xl border border-ink-200 bg-white p-4 transition hover:border-brand-600/40"
            >
                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                    <x-dynamic-component :component="'heroicon-o-'.$endpoint['icon']" class="size-4.5" />
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-ink-900">{{ $endpoint['label'] }}</span>
                    <span class="clamp-1 block text-xs text-ink-500">{{ $endpoint['url'] }}</span>
                </span>
                <x-heroicon-m-arrow-top-right-on-square class="ml-auto size-4 shrink-0 text-ink-300" />
            </a>
        @endforeach
    </div>

    <form wire:submit="save">
        <div class="space-y-5">
            {{-- Site-wide --}}
            <x-admin.panel title="Site-wide defaults" description="Used whenever a page does not set its own.">
                <div class="space-y-4">
                    <x-ui.input wire:model="defaultTitle" name="defaultTitle" label="Default page title" required />
                    <x-ui.textarea wire:model="defaultDescription" name="defaultDescription" label="Default meta description" :rows="2" required hint="Aim for 150–160 characters." />
                    <x-ui.input wire:model="twitterHandle" name="twitterHandle" label="Twitter / X handle" />
                </div>
            </x-admin.panel>

            {{-- LLM --}}
            <x-admin.panel
                title="Language models"
                description="Published at /llms.txt — the emerging convention for telling answer engines what a site contains."
            >
                <div class="space-y-4">
                    <label class="flex cursor-pointer items-start gap-2.5">
                        <input type="checkbox" wire:model="llmsEnabled" class="mt-0.5 size-4 rounded border-ink-300 text-brand-600">
                        <span>
                            <span class="block text-sm font-medium text-ink-800">Publish llms.txt and llms-full.txt</span>
                            <span class="mt-0.5 block text-xs text-ink-500">
                                Also adds an explicit welcome for GPTBot, ClaudeBot, PerplexityBot and others in robots.txt.
                            </span>
                        </span>
                    </label>

                    <x-ui.textarea
                        wire:model="siteSummary"
                        name="siteSummary"
                        label="Site summary"
                        :rows="4"
                        required
                        hint="A factual paragraph describing what HanbellShop is. This is what a model reads first."
                    />
                </div>
            </x-admin.panel>

            {{-- Per-route overrides --}}
            <x-admin.panel title="Page overrides" description="Leave a field blank to fall back to the defaults above." padding="none">
                <div class="divide-y divide-ink-100">
                    @foreach ($managedRoutes as $route => $label)
                        <div class="p-5">
                            <p class="mb-3 text-sm font-bold text-ink-900">{{ $label }}</p>
                            <p class="mb-3 font-mono text-[10px] text-ink-300">{{ $route }}</p>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-ui.input wire:model="routes.{{ $route }}.title" name="title_{{ $loop->index }}" label="Title" />
                                <x-ui.input wire:model="routes.{{ $route }}.description" name="desc_{{ $loop->index }}" label="Meta description" />
                            </div>

                            <div class="mt-3">
                                <x-ui.textarea
                                    wire:model="routes.{{ $route }}.llm_summary"
                                    name="llm_{{ $loop->index }}"
                                    label="LLM summary"
                                    :rows="2"
                                />
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-admin.panel>

            {{-- FAQ, which becomes FAQPage structured data --}}
            <x-admin.panel
                title="Frequently asked questions"
                description="Emitted as FAQPage structured data on the homepage and published in llms.txt. This is often what an answer engine quotes."
            >
                <div class="space-y-4">
                    @foreach ($faqs as $index => $faq)
                        <div class="rounded-xl border border-ink-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1 space-y-3">
                                    <x-ui.input wire:model="faqs.{{ $index }}.question" name="faq_q_{{ $index }}" label="Question" />
                                    <x-ui.textarea wire:model="faqs.{{ $index }}.answer" name="faq_a_{{ $index }}" label="Answer" :rows="2" />
                                </div>

                                <button
                                    type="button"
                                    wire:click="removeFaq({{ $index }})"
                                    class="mt-1 shrink-0 rounded-md p-1.5 text-ink-400 transition hover:bg-danger-50 hover:text-danger-600"
                                    aria-label="{{ __('hanbell.common.delete') }}"
                                >
                                    <x-heroicon-o-trash class="size-4" />
                                </button>
                            </div>
                        </div>
                    @endforeach

                    <x-ui.button type="button" wire:click="addFaq" variant="outline" size="sm">
                        <x-heroicon-o-plus class="size-4" />
                        Add a question
                    </x-ui.button>
                </div>
            </x-admin.panel>

            {{-- Raw robots.txt --}}
            <x-admin.panel title="Extra robots.txt rules" description="Appended verbatim to the generated file.">
                <x-ui.textarea wire:model="robotsExtra" name="robotsExtra" :rows="4" />
            </x-admin.panel>

            <x-ui.button type="submit" variant="primary" size="lg" loading="save">
                <span wire:loading.remove wire:target="save">{{ __('hanbell.common.save_changes') }}</span>
                <span wire:loading wire:target="save">{{ __('hanbell.common.loading') }}</span>
            </x-ui.button>
        </div>
    </form>
</div>
