<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ \App\Support\Locale::direction() }}"
    class="scroll-pt-32"
>
<head>
    @include('partials.head-meta', ['seo' => $seo ?? app(\App\Support\Seo::class)])
</head>

<body class="min-h-dvh bg-white font-sans text-ink-900 antialiased">

    {{-- Keyboard users land here first; a marketplace header is long. --}}
    <a
        href="#main"
        class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-ink-950 focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-white"
    >
        {{ __('hanbell.nav.skip_to_content') }}
    </a>

    @include('partials.header')

    @if (session('status'))
        <div class="hb-container pt-4">
            <x-ui.alert variant="success" :dismissible="true">{{ session('status') }}</x-ui.alert>
        </div>
    @endif

    @if (session('error'))
        <div class="hb-container pt-4">
            <x-ui.alert variant="danger" :dismissible="true">{{ session('error') }}</x-ui.alert>
        </div>
    @endif

    <main id="main" class="pb-20 sm:pb-0">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @include('partials.footer')

    {{-- Toast host. Everything mutating dispatches a `toast` event, so no action
         ever needs a full page reload to give feedback. --}}
    <x-site.toast-container />

    {{-- Branded page-transition loader for wire:navigate. --}}
    <x-site.page-loader />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireScripts
    @stack('scripts')
</body>
</html>
