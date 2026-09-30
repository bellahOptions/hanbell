<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head-meta', ['seo' => $seo ?? app(\App\Support\Seo::class)])
</head>

<body class="min-h-dvh bg-ink-50 font-sans text-ink-900 antialiased">
    <a href="#vendor-main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-ink-950 focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-white">
        {{ __('hanbell.nav.skip_to_content') }}
    </a>

    @include('partials.vendor-nav')

    <main id="vendor-main" class="hb-container py-6 lg:py-8">
        {{ $slot ?? '' }}
    </main>

    <x-site.toast-container />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireScripts
    @stack('scripts')
</body>
</html>
