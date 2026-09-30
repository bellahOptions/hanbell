<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head-meta', ['seo' => $seo ?? app(\App\Support\Seo::class)])
    @stack('head')
</head>

<body
    class="min-h-dvh bg-ink-50 font-sans text-ink-900 antialiased"
    x-data="{ sidebarOpen: false }"
>
    <a
        href="#admin-main"
        class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-ink-950 focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-white"
    >
        {{ __('hanbell.nav.skip_to_content') }}
    </a>

    <div class="flex min-h-dvh">
        @include('partials.admin-sidebar')

        <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
            @include('partials.admin-topbar')

            <main id="admin-main" class="flex-1 p-4 sm:p-6 lg:p-8">
                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Mobile sidebar scrim --}}
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        x-cloak
        @click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-ink-950/50 lg:hidden"
        aria-hidden="true"
    ></div>

    <x-site.toast-container />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireScripts
    @stack('scripts')
</body>
</html>
