@props([
    'search' => null,
    'searchPlaceholder' => null,
])

{{--
    Admin toolbar: search, filters and the primary action for a list screen.
    Kept as one component so every admin list has the same control rhythm.
--}}
<div {{ $attributes->merge(['class' => 'mb-4 flex flex-wrap items-center gap-2']) }}>
    @if ($search !== null)
        <div class="relative min-w-0 flex-1 sm:max-w-xs">
            <label for="admin-search" class="sr-only">{{ __('hanbell.admin.search_placeholder') }}</label>
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-400" />
            <input
                id="admin-search"
                type="search"
                wire:model.live.debounce.300ms="{{ $search }}"
                placeholder="{{ $searchPlaceholder ?? __('hanbell.admin.search_placeholder') }}"
                class="h-10 w-full rounded-lg border border-ink-300 bg-white pl-9 pr-3 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20"
            >
        </div>
    @endif

    {{ $slot }}

    @isset($actions)
        <div class="ml-auto flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
