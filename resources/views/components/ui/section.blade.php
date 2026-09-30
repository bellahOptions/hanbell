@props([
    'title' => null,
    'subtitle' => null,
    'eyebrow' => null,
    'href' => null,
    'linkLabel' => null,
    'align' => 'left',
    'spacing' => 'md',
])

@php
    /**
     * A titled band of content — the homepage is composed almost entirely of
     * these, so the heading hierarchy and the "see all" affordance live in one
     * place rather than being re-invented per section.
     */
    $spacings = [
        'sm' => 'py-8',
        'md' => 'py-12',
        'lg' => 'py-16',
    ];
@endphp

<section {{ $attributes->merge(['class' => $spacings[$spacing] ?? $spacings['md']]) }}>
    <div class="hb-container">
        @if ($title || $eyebrow || $href)
            <header class="mb-6 flex flex-wrap items-end justify-between gap-4 sm:mb-8">
                <div class="{{ $align === 'center' ? 'mx-auto text-center' : '' }}">
                    @if ($eyebrow)
                        <p class="hb-eyebrow mb-2 text-brand-600">{{ $eyebrow }}</p>
                    @endif

                    @if ($title)
                        <h2 class="text-2xl font-bold tracking-tight text-ink-950 sm:text-3xl">
                            {{ $title }}
                        </h2>
                    @endif

                    @if ($subtitle)
                        <p class="mt-2 max-w-2xl text-sm text-ink-500 sm:text-base">{{ $subtitle }}</p>
                    @endif
                </div>

                @if ($href)
                    <a
                        href="{{ $href }}"
                        wire:navigate
                        class="group inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 transition hover:text-brand-800"
                    >
                        {{ $linkLabel ?? __('hanbell.common.view_all') }}
                        <x-heroicon-m-arrow-right class="size-4 transition-transform group-hover:translate-x-0.5" />
                    </a>
                @endif
            </header>
        @endif

        {{ $slot }}
    </div>
</section>
