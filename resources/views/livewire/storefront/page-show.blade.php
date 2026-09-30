<div class="hb-container py-8 sm:py-10">
    <div class="grid gap-8 lg:grid-cols-12">
        {{-- Policy navigation --}}
        @if ($policyPages->isNotEmpty())
            <aside class="lg:col-span-3">
                <nav class="rounded-xl border border-ink-200 bg-white p-4 lg:sticky lg:top-32" aria-label="{{ __('hanbell.page.policies') }}">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wider text-ink-400">{{ __('hanbell.page.policies') }}</p>

                    <ul class="space-y-1">
                        @foreach ($policyPages as $policy)
                            <li>
                                <a
                                    href="{{ $policy->publicUrl() }}"
                                    wire:navigate
                                    @class([
                                        'block rounded-md px-2.5 py-2 text-sm transition',
                                        'bg-brand-50 font-semibold text-brand-700' => $page && $page->id === $policy->id,
                                        'text-ink-600 hover:bg-ink-50 hover:text-ink-900' => ! $page || $page->id !== $policy->id,
                                    ])
                                    @if ($page && $page->id === $policy->id) aria-current="page" @endif
                                >
                                    {{ $policy->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </aside>
        @endif

        <div class="{{ $policyPages->isNotEmpty() ? 'lg:col-span-9' : 'mx-auto max-w-3xl lg:col-span-12' }}">
            @if ($page)
                <article>
                    <header class="mb-6 border-b border-ink-100 pb-5">
                        <h1 class="text-3xl font-extrabold tracking-tight text-ink-950">{{ $page->title }}</h1>

                        @if ($page->published_at)
                            <p class="mt-2 text-xs text-ink-400">
                                {{ __('hanbell.page.last_updated', ['date' => $page->updated_at->format('j F Y')]) }}
                            </p>
                        @endif

                        @if ($page->excerpt)
                            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-ink-600">{{ $page->excerpt }}</p>
                        @endif
                    </header>

                    {{-- Admin-authored content. The model strips script/iframe and
                         allow-lists formatting tags; the editor is admin-only. --}}
                    <div class="prose-hb max-w-none text-sm leading-relaxed text-ink-700
                                [&_h2]:mt-8 [&_h2]:text-lg [&_h2]:font-bold [&_h2]:text-ink-950
                                [&_h3]:mt-6 [&_h3]:font-bold [&_h3]:text-ink-900
                                [&_p]:mt-4 [&_ul]:mt-4 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:mt-4 [&_ol]:list-decimal [&_ol]:pl-5
                                [&_li]:mt-1.5 [&_a]:font-semibold [&_a]:text-brand-700 [&_a]:underline [&_a]:underline-offset-2
                                [&_strong]:text-ink-900 [&_table]:mt-4 [&_table]:w-full [&_table]:text-sm
                                [&_th]:border-b [&_th]:border-ink-200 [&_th]:px-3 [&_th]:py-2 [&_th]:text-left [&_th]:font-semibold
                                [&_td]:border-b [&_td]:border-ink-100 [&_td]:px-3 [&_td]:py-2">
                        {!! $page->renderedContent() !!}
                    </div>
                </article>
            @else
                {{-- A policy an administrator has not written yet. Not a 404:
                     the footer links here, and a friendly placeholder beats a
                     dead end. Kept out of the index. --}}
                <x-ui.card padding="none">
                    <x-ui.empty-state
                        icon="document-text"
                        :title="__('hanbell.page.not_found')"
                        :message="__('hanbell.page.not_found_hint')"
                        :action-label="__('hanbell.page.back_to_shop')"
                        :action-href="route('storefront.shop')"
                    />
                </x-ui.card>
            @endif
        </div>
    </div>
</div>
