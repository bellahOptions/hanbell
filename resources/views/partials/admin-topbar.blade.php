@php
    /**
     * Admin topbar: the current section, the search affordance, and the
     * account menu. Kept thin so the page content gets the vertical space.
     */
    $user = auth()->user();

    // A readable section name derived from the route, so a page cannot forget
    // to declare its own heading context.
    $section = str(request()->route()?->getName() ?? '')
        ->after('admin.')
        ->before('.')
        ->headline()
        ->toString();
@endphp

<header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-ink-200 bg-white/95 px-4 backdrop-blur-md sm:px-6">
    <button
        type="button"
        @click="sidebarOpen = true"
        class="rounded-lg p-2 text-ink-500 transition hover:bg-ink-100 lg:hidden"
        aria-label="{{ __('hanbell.nav.open_menu') }}"
    >
        <x-heroicon-o-bars-3 class="size-5" />
    </button>

    <div class="min-w-0">
        <p class="clamp-1 text-sm font-bold text-ink-950">
            {{ $title ?? $section ?: __('hanbell.admin.dashboard') }}
        </p>
        @hasSection('breadcrumb')
            <div class="mt-0.5 text-[11px] text-ink-400">@yield('breadcrumb')</div>
        @endif
    </div>

    <div class="ml-auto flex items-center gap-2">
        {{-- Locale switcher: the admin UI itself is translatable. --}}
        <div class="relative hidden sm:block" x-data="{ open: false }" @click.outside="open = false">
            <button
                type="button"
                @click="open = !open"
                class="flex items-center gap-1.5 rounded-lg px-2.5 py-2 text-xs font-medium text-ink-500 transition hover:bg-ink-100"
                :aria-expanded="open"
            >
                <x-heroicon-o-globe-alt class="size-4" />
                {{ \App\Support\Locale::name() }}
            </button>

            <div
                x-show="open"
                x-transition.origin.top.right
                x-cloak
                class="absolute right-0 top-full z-50 mt-2 w-44 overflow-hidden rounded-xl border border-ink-200 bg-white py-1 shadow-lift"
            >
                @foreach (\App\Support\Locale::all() as $code => $meta)
                    <a
                        href="{{ route('locale.update', $code) }}"
                        class="flex items-center gap-2 px-3 py-2 text-sm transition hover:bg-ink-50 {{ app()->getLocale() === $code ? 'font-semibold text-brand-700' : 'text-ink-700' }}"
                    >
                        <span aria-hidden="true">{{ $meta['flag'] }}</span>
                        {{ $meta['native'] }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Account --}}
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button
                type="button"
                @click="open = !open"
                class="flex items-center gap-2 rounded-lg p-1.5 transition hover:bg-ink-100"
                :aria-expanded="open"
                aria-label="{{ __('hanbell.nav.account') }}"
            >
                <span class="flex size-8 items-center justify-center rounded-full bg-ink-950 text-[11px] font-bold text-white">
                    {{ $user?->initials() }}
                </span>
                <x-heroicon-m-chevron-down class="size-3.5 text-ink-400" />
            </button>

            <div
                x-show="open"
                x-transition.origin.top.right
                x-cloak
                class="absolute right-0 top-full z-50 mt-2 w-56 overflow-hidden rounded-xl border border-ink-200 bg-white py-1 shadow-lift"
            >
                <div class="border-b border-ink-100 px-3.5 py-2.5">
                    <p class="clamp-1 text-sm font-semibold text-ink-900">{{ $user?->name }}</p>
                    <p class="clamp-1 text-xs text-ink-500">{{ $user?->email }}</p>
                </div>

                <a href="{{ route('storefront.account.security') }}" wire:navigate class="flex items-center gap-2.5 px-3.5 py-2.5 text-sm text-ink-700 transition hover:bg-ink-50">
                    <x-heroicon-o-shield-check class="size-4 text-ink-400" />
                    {{ __('hanbell.account.security') }}
                </a>

                <a href="{{ route('storefront.home') }}" wire:navigate class="flex items-center gap-2.5 px-3.5 py-2.5 text-sm text-ink-700 transition hover:bg-ink-50">
                    <x-heroicon-o-arrow-top-right-on-square class="size-4 text-ink-400" />
                    {{ __('hanbell.admin.back_to_store') }}
                </a>

                <div class="my-1 border-t border-ink-100"></div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2.5 text-left text-sm text-ink-700 transition hover:bg-ink-50">
                        <x-heroicon-o-arrow-right-on-rectangle class="size-4 text-ink-400" />
                        {{ __('hanbell.common.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
