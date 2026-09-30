@props([
    'icon' => 'magnifying-glass',
    'title' => null,
    'message' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

{{-- Empty state. Every list in the storefront has one, so "nothing here yet"
     never renders as a blank void with no way forward. --}}
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-16 text-center']) }}>
    <span class="mb-5 flex size-16 items-center justify-center rounded-2xl bg-ink-100 text-ink-400">
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-8" />
    </span>

    @if ($title)
        <h3 class="text-lg font-bold text-ink-900">{{ $title }}</h3>
    @endif

    @if ($message)
        <p class="mt-2 max-w-md text-sm text-ink-500">{{ $message }}</p>
    @endif

    @if ($actionLabel && $actionHref)
        <x-ui.button :href="$actionHref" variant="primary" class="mt-6">
            {{ $actionLabel }}
        </x-ui.button>
    @endif

    {{ $slot }}
</div>
