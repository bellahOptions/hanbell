@props([
    'title' => null,
    'description' => null,
    'padded' => true,
])

{{--
    shadcn-style panel: a hairline border, a low-radius surface and a plain
    header. The admin UI deliberately uses less colour than the storefront so
    that status colours and charts are the only things that draw the eye.
--}}
<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-ink-200 bg-white']) }}>
    @if ($title || isset($actions))
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-5 py-4">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="text-sm font-bold text-ink-950">{{ $title }}</h2>
                @endif

                @if ($description)
                    <p class="mt-0.5 text-xs text-ink-500">{{ $description }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div class="{{ $padded ? 'p-5' : '' }}">
        {{ $slot }}
    </div>
</section>
