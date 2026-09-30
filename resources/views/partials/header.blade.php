@php
    use App\Livewire\Storefront\HeaderCounts;
    use App\Support\Locale;

    /**
     * Storefront header.
     *
     * Structure follows what Nigerian shoppers already know from the big local
     * marketplaces — a dense bar with the search field always visible, a
     * category rail underneath, and the bag/wishlist counts in the top right —
     * but rendered in HanbellShop's own green/yellow/black palette and with the
     * brand's editorial type.
     */
    $departments = \App\Models\Department::query()
        ->active()
        ->ordered()
        ->with(['categories' => fn ($q) => $q->active()->ordered()])
        ->get();

    $user = auth()->user();
    $isAdmin = $user?->isAdmin() ?? false;
@endphp

<div class="sticky top-0 z-50 border-b border-ink-200 bg-white/95 backdrop-blur-md" x-data="{ mobileOpen: false, langOpen: false }">

    {{-- Utility strip: the free-delivery promise, language and account links. --}}
    <div class="bg-brand-600 text-white">
        <div class="hb-container flex h-9 items-center justify-between gap-4 text-xs">
            <p class="flex items-center gap-1.5 font-medium">
                <x-heroicon-m-truck class="size-3.5 shrink-0" />
                <span class="hidden sm:inline">
                    {{ __('hanbell.cart.free_shipping') }} ·
                    {{ __('hanbell.shipping.free_over', ['amount' => \App\Support\Money::format((int) config('hanbell.shipping.free_threshold_minor', 5000000))]) }}
                </span>
                <span class="sm:hidden">{{ __('hanbell.cart.free_shipping') }}</span>
            </p>

            <div class="flex items-center gap-3">
                {{-- Language switcher --}}
                <div class="relative" @click.outside="langOpen = false">
                    <button
                        type="button"
                        @click="langOpen = !langOpen"
                        class="flex items-center gap-1 font-medium transition hover:text-accent-300"
                        aria-haspopup="true"
                        :aria-expanded="langOpen"
                    >
                        <x-heroicon-m-globe-alt class="size-3.5" />
                        <span>{{ Locale::name() }}</span>
                        <x-heroicon-m-chevron-down class="size-3" />
                    </button>

                    <div
                        x-show="langOpen"
                        x-transition.origin.top.right
                        x-cloak
                        class="absolute right-0 top-full z-50 mt-2 w-48 overflow-hidden rounded-xl border border-ink-200 bg-white py-1 text-ink-900 shadow-lift"
                        role="menu"
                    >
                        @foreach (Locale::all() as $code => $meta)
                            <a
                                href="{{ route('locale.update', $code) }}"
                                class="flex items-center justify-between gap-2 px-3 py-2 text-sm transition hover:bg-ink-50 {{ app()->getLocale() === $code ? 'font-semibold text-brand-700' : 'text-ink-700' }}"
                                role="menuitem"
                                hreflang="{{ $code }}"
                            >
                                <span class="flex items-center gap-2">
                                    <span aria-hidden="true">{{ $meta['flag'] }}</span>
                                    <span>{{ $meta['native'] }}</span>
                                </span>
                                @if (app()->getLocale() === $code)
                                    <x-heroicon-m-check class="size-4" />
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

                <span class="hidden h-3 w-px bg-white/25 sm:block" aria-hidden="true"></span>

                <a href="{{ route('storefront.contact') }}" wire:navigate class="hidden font-medium transition hover:text-accent-300 sm:inline">
                    {{ __('hanbell.nav.help') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Main bar --}}
    <div class="hb-container">
        <div class="flex h-16 items-center gap-4">
            <x-site.logo class="shrink-0" height="h-7 sm:h-8" />

            {{-- Search: always visible, as on the marketplaces shoppers know. --}}
            <form
                action="{{ route('storefront.search') }}"
                method="GET"
                class="relative hidden flex-1 md:block"
                role="search"
            >
                <label for="site-search" class="sr-only">{{ __('hanbell.search.label') }}</label>

                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-ink-400" />

                <input
                    id="site-search"
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="{{ __('hanbell.search.placeholder') }}"
                    autocomplete="off"
                    class="h-11 w-full rounded-full border border-ink-200 bg-ink-50 pl-10 pr-24 text-sm text-ink-900 placeholder:text-ink-400 transition focus:border-brand-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-600/20"
                >

                <button
                    type="submit"
                    class="absolute right-1.5 top-1/2 h-8 -translate-y-1/2 rounded-full bg-brand-600 px-4 text-xs font-bold text-white transition hover:bg-brand-700"
                >
                    {{ __('hanbell.search.submit') }}
                </button>
            </form>

            <div class="ml-auto flex items-center gap-1">
                {{-- Account --}}
                @auth
                    <div class="relative hidden sm:block" x-data="{ open: false }" @click.outside="open = false">
                        <button
                            type="button"
                            @click="open = !open"
                            class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-sm font-medium text-ink-700 transition hover:bg-ink-100"
                            :aria-expanded="open"
                        >
                            <span class="flex size-7 items-center justify-center rounded-full bg-brand-600 text-[11px] font-bold text-white">
                                {{ $user->initials() }}
                            </span>
                            <span class="hidden max-w-24 truncate lg:inline">{{ $user->firstName() }}</span>
                            <x-heroicon-m-chevron-down class="size-3.5" />
                        </button>

                        <div
                            x-show="open"
                            x-transition.origin.top.right
                            x-cloak
                            class="absolute right-0 top-full z-50 mt-2 w-56 overflow-hidden rounded-xl border border-ink-200 bg-white py-1 shadow-lift"
                        >
                            @if ($isAdmin)
                                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 text-sm text-ink-700 transition hover:bg-ink-50">
                                    <x-heroicon-o-squares-2x2 class="size-4 text-ink-400" />
                                    {{ __('hanbell.admin.admin_panel') }}
                                </a>
                            @else
                                <a href="{{ route('storefront.account.dashboard') }}" wire:navigate class="flex items-center gap-2.5 px-3.5 py-2.5 text-sm text-ink-700 transition hover:bg-ink-50">
                                    <x-heroicon-o-user class="size-4 text-ink-400" />
                                    {{ __('hanbell.nav.my_account') }}
                                </a>
                                <a href="{{ route('storefront.account.orders') }}" wire:navigate class="flex items-center gap-2.5 px-3.5 py-2.5 text-sm text-ink-700 transition hover:bg-ink-50">
                                    <x-heroicon-o-cube class="size-4 text-ink-400" />
                                    {{ __('hanbell.nav.my_orders') }}
                                </a>
                                <a href="{{ route('storefront.account.wishlist') }}" wire:navigate class="flex items-center gap-2.5 px-3.5 py-2.5 text-sm text-ink-700 transition hover:bg-ink-50">
                                    <x-heroicon-o-heart class="size-4 text-ink-400" />
                                    {{ __('hanbell.nav.wishlist') }}
                                </a>
                                <a href="{{ route('storefront.account.security') }}" wire:navigate class="flex items-center gap-2.5 px-3.5 py-2.5 text-sm text-ink-700 transition hover:bg-ink-50">
                                    <x-heroicon-o-shield-check class="size-4 text-ink-400" />
                                    {{ __('hanbell.account.security') }}
                                </a>
                            @endif

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
                @else
                    <a
                        href="{{ route('login') }}"
                        class="hidden items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-ink-700 transition hover:bg-ink-100 sm:flex"
                    >
                        <x-heroicon-o-user class="size-4.5" />
                        {{ __('hanbell.common.login') }}
                    </a>
                @endauth

                {{-- Wishlist + bag counts, live-updated by the HeaderCounts component. --}}
                @unless ($isAdmin)
                    <livewire:storefront.header-counts />

                    <a
                        href="{{ route('storefront.wishlist') }}"
                        wire:navigate
                        class="relative hidden rounded-lg p-2.5 text-ink-700 transition hover:bg-ink-100 sm:flex"
                        aria-label="{{ __('hanbell.nav.wishlist') }}"
                    >
                        <x-heroicon-o-heart class="size-5" />
                    </a>
                @endunless

                {{-- Mobile menu toggle --}}
                <button
                    type="button"
                    @click="mobileOpen = !mobileOpen"
                    class="rounded-lg p-2.5 text-ink-700 transition hover:bg-ink-100 lg:hidden"
                    :aria-expanded="mobileOpen"
                    aria-label="{{ __('hanbell.nav.menu') }}"
                >
                    <x-heroicon-o-bars-3 x-show="!mobileOpen" class="size-5" />
                    <x-heroicon-o-x-mark x-show="mobileOpen" x-cloak class="size-5" />
                </button>
            </div>
        </div>

        {{-- Mobile search --}}
        <form action="{{ route('storefront.search') }}" method="GET" class="relative pb-3 md:hidden" role="search">
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 -mt-1.5 text-ink-400" />
            <input
                type="search"
                name="q"
                value="{{ request('q') }}"
                placeholder="{{ __('hanbell.search.placeholder') }}"
                autocomplete="off"
                class="h-11 w-full rounded-full border border-ink-200 bg-ink-50 pl-10 pr-4 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-600/20"
            >
        </form>
    </div>

    {{-- Category rail (desktop) --}}
    <nav class="hidden border-t border-ink-100 lg:block" aria-label="{{ __('hanbell.nav.categories') }}">
        <div class="hb-container flex h-11 items-center gap-1 text-sm">
            <a
                href="{{ route('storefront.shop') }}"
                wire:navigate
                class="rounded-md px-3 py-1.5 font-semibold text-ink-900 transition hover:bg-ink-100 {{ request()->routeIs('storefront.shop') ? 'text-brand-700' : '' }}"
            >
                {{ __('hanbell.nav.all_products') }}
            </a>

            @foreach ($departments as $department)
                <div class="group relative">
                    <a
                        href="{{ $department->publicUrl() }}"
                        class="flex items-center gap-1 rounded-md px-3 py-1.5 font-medium text-ink-600 transition hover:bg-ink-100 hover:text-ink-900"
                    >
                        {{ $department->name }}
                        @if ($department->categories->isNotEmpty())
                            <x-heroicon-m-chevron-down class="size-3 opacity-50" />
                        @endif
                    </a>

                    {{-- Mega-menu: only for departments that actually have children. --}}
                    @if ($department->categories->isNotEmpty())
                        <div class="invisible absolute left-0 top-full z-50 w-[min(58rem,calc(100vw-4rem))] pt-1 opacity-0 transition-all duration-150 group-hover:visible group-hover:opacity-100">
                            <div class="overflow-hidden rounded-xl border border-ink-200 bg-white p-5 shadow-pop">
                                <div class="grid grid-cols-4 gap-x-6 gap-y-2">
                                    @foreach ($department->categories as $category)
                                        <a
                                            href="{{ $category->publicUrl() }}"
                                            wire:navigate
                                            class="rounded-md px-2 py-1.5 text-sm text-ink-600 transition hover:bg-ink-50 hover:text-brand-700"
                                        >
                                            {{ $category->name }}
                                        </a>
                                    @endforeach
                                </div>

                                <div class="mt-4 border-t border-ink-100 pt-3">
                                    <a href="{{ $department->publicUrl() }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-800">
                                        {{ __('hanbell.common.view_all') }} · {{ $department->name }}
                                        <x-heroicon-m-arrow-right class="size-3.5" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="ml-auto flex items-center gap-4">
                <a href="{{ route('storefront.vendors.index') }}" wire:navigate class="font-medium text-ink-600 transition hover:text-brand-700">
                    {{ __('hanbell.nav.brands') }}
                </a>
                <a href="{{ route('storefront.vendors.apply') }}" wire:navigate class="flex items-center gap-1.5 font-semibold text-brand-700 transition hover:text-brand-800">
                    <x-heroicon-o-sparkles class="size-4" />
                    {{ __('hanbell.nav.sell_with_us') }}
                </a>
            </div>
        </div>
    </nav>

    {{-- Mobile drawer --}}
    <div
        x-show="mobileOpen"
        x-transition.opacity
        x-cloak
        class="fixed inset-0 top-[6.25rem] z-40 bg-ink-950/40 lg:hidden"
        @click="mobileOpen = false"
        aria-hidden="true"
    ></div>

    <div
        x-show="mobileOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        x-cloak
        class="fixed bottom-0 left-0 top-[6.25rem] z-40 w-[86%] max-w-sm overflow-y-auto border-r border-ink-200 bg-white pb-24 lg:hidden"
        role="dialog"
        aria-label="{{ __('hanbell.nav.menu') }}"
    >
        <nav class="p-4">
            <a href="{{ route('storefront.shop') }}" wire:navigate class="flex items-center justify-between rounded-lg px-3 py-3 font-semibold text-ink-900 transition hover:bg-ink-50">
                {{ __('hanbell.nav.all_products') }}
                <x-heroicon-m-chevron-right class="size-4 text-ink-400" />
            </a>

            @foreach ($departments as $department)
                <div class="mt-1" x-data="{ open: false }">
                    <div class="flex items-center">
                        <a href="{{ $department->publicUrl() }}" class="flex-1 rounded-lg px-3 py-3 font-semibold text-ink-900 transition hover:bg-ink-50">
                            {{ $department->name }}
                        </a>

                        @if ($department->categories->isNotEmpty())
                            <button
                                type="button"
                                @click="open = !open"
                                class="rounded-lg p-3 text-ink-400 transition hover:bg-ink-50"
                                :aria-expanded="open"
                                aria-label="{{ __('hanbell.common.show_more') }}"
                            >
                                <x-heroicon-m-chevron-down class="size-4 transition" ::class="open && 'rotate-180'" />
                            </button>
                        @endif
                    </div>

                    @if ($department->categories->isNotEmpty())
                        <div x-show="open" x-collapse x-cloak class="ml-3 border-l border-ink-200 pl-3">
                            @foreach ($department->categories as $category)
                                <a href="{{ $category->publicUrl() }}" wire:navigate class="block rounded-lg px-3 py-2 text-sm text-ink-600 transition hover:bg-ink-50 hover:text-brand-700">
                                    {{ $category->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="my-3 border-t border-ink-100"></div>

            <a href="{{ route('storefront.vendors.index') }}" wire:navigate class="flex items-center gap-2.5 rounded-lg px-3 py-3 font-medium text-ink-700 transition hover:bg-ink-50">
                <x-heroicon-o-building-storefront class="size-4.5 text-ink-400" />
                {{ __('hanbell.nav.brands') }}
            </a>

            <a href="{{ route('storefront.vendors.apply') }}" wire:navigate class="flex items-center gap-2.5 rounded-lg px-3 py-3 font-medium text-ink-700 transition hover:bg-ink-50">
                <x-heroicon-o-sparkles class="size-4.5 text-ink-400" />
                {{ __('hanbell.nav.sell_with_us') }}
            </a>

            <a href="{{ route('storefront.contact') }}" wire:navigate class="flex items-center gap-2.5 rounded-lg px-3 py-3 font-medium text-ink-700 transition hover:bg-ink-50">
                <x-heroicon-o-lifebuoy class="size-4.5 text-ink-400" />
                {{ __('hanbell.nav.help') }}
            </a>

            <div class="my-3 border-t border-ink-100"></div>

            @guest
                <div class="flex gap-2 px-1">
                    <x-ui.button :href="route('login')" variant="outline" block>{{ __('hanbell.common.login') }}</x-ui.button>
                    <x-ui.button :href="route('register')" variant="primary" block>{{ __('hanbell.common.register') }}</x-ui.button>
                </div>
            @endguest
        </nav>
    </div>
</div>
