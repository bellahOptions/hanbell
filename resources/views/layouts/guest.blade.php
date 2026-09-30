<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head-meta', ['seo' => $seo ?? app(\App\Support\Seo::class)])
</head>

<body class="min-h-dvh bg-white font-sans text-ink-900 antialiased">

    {{--
        Split-screen auth layout: a dark brand panel on the left carrying the
        argument (why HanbellShop is worth an account) and the form on the
        right. On mobile the panel collapses to a compact header so the form is
        immediately reachable.
    --}}
    <div class="grid min-h-dvh lg:grid-cols-2">

        {{-- Brand panel --}}
        <aside class="relative order-1 overflow-hidden bg-ink-950 px-6 py-10 text-white lg:order-none lg:px-12 lg:py-14">
            <div class="absolute inset-0 bg-[radial-gradient(55rem_28rem_at_10%_-15%,rgba(23,133,8,0.55),transparent_62%),radial-gradient(38rem_20rem_at_95%_110%,rgba(255,242,0,0.16),transparent_60%)]"></div>

            <div class="relative flex h-full flex-col">
                <x-site.logo variant="light" height="h-7" />

                <div class="mt-10 lg:mt-auto">
                    <p class="hb-eyebrow mb-3 text-accent-300">{{ __('hanbell.legal.nigerian_made') }}</p>

                    <h2 class="max-w-sm text-2xl font-extrabold leading-tight tracking-tight sm:text-3xl">
                        {{ __('hanbell.brand.tagline') }}
                    </h2>

                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-ink-300">
                        {{ __('hanbell.brand.description') }}
                    </p>

                    <ul class="mt-8 space-y-3">
                        @foreach ([
                            ['icon' => 'sparkles', 'text' => __('hanbell.legal.approved_quality')],
                            ['icon' => 'banknotes', 'text' => __('hanbell.legal.fair_prices_hint')],
                            ['icon' => 'truck', 'text' => __('hanbell.legal.nationwide_delivery_hint')],
                            ['icon' => 'shield-check', 'text' => __('hanbell.legal.secure_checkout_hint')],
                        ] as $point)
                            <li class="flex items-start gap-2.5 text-sm text-ink-200">
                                <x-dynamic-component :component="'heroicon-o-'.$point['icon']" class="mt-0.5 size-4 shrink-0 text-accent-300" />
                                <span>{{ $point['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="mt-10 text-xs text-ink-500 lg:mt-12">
                    &copy; {{ now()->year }} {{ config('hanbell.name') }}. {{ __('hanbell.footer.rights') }}
                </p>
            </div>
        </aside>

        {{-- Form panel --}}
        <main class="order-2 flex items-center justify-center px-5 py-10 sm:px-8 lg:px-12">
            <div class="w-full max-w-md">
                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </main>
    </div>

    <x-site.toast-container />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireScripts
    @stack('scripts')
</body>
</html>
