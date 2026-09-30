@php
    use App\Models\Page;

    /**
     * Storefront footer.
     *
     * The policy column is generated from the CMS rather than hard-coded, so an
     * administrator can add or reorder policy pages without a deploy — and
     * every link is guaranteed to resolve, because the page list and the routes
     * come from the same table.
     */
    $policyPages = Page::query()->inFooter()->ofGroup('policy')->orderBy('position')->get();
    $helpPages = Page::query()->inFooter()->ofGroup('help')->orderBy('position')->get();
    $companyPages = Page::query()->inFooter()->ofGroup('general')->orderBy('position')->get();

    $departments = \App\Models\Department::query()->active()->ordered()->limit(6)->get();
@endphp

<footer class="mt-16 bg-ink-950 text-ink-300">
    {{-- Newsletter sits above the link columns: the highest-value action in the
         footer should be the first thing reached when scrolling to it. --}}
    <div class="border-b border-white/10">
        <div class="hb-container py-12">
            <div class="grid items-center gap-8 lg:grid-cols-2">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                        {{ __('hanbell.newsletter.title') }}
                    </h2>
                    <p class="mt-2 max-w-md text-sm text-ink-400">{{ __('hanbell.newsletter.subtitle') }}</p>
                </div>

                <livewire:storefront.newsletter-signup />
            </div>
        </div>
    </div>

    <div class="hb-container py-14">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-5">
            {{-- Brand column --}}
            <div class="lg:col-span-2">
                <x-site.logo variant="light" height="h-8" class="[&_img]:brightness-0 [&_img]:invert-0" />

                <p class="mt-5 max-w-sm text-sm leading-relaxed text-ink-400">
                    {{ config('hanbell.description') }}
                </p>

                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach (['instagram', 'twitter', 'facebook', 'tiktok'] as $network)
                        @if ($url = \App\Support\Settings::get("social.{$network}"))
                            <a
                                href="{{ $url }}"
                                target="_blank"
                                rel="noopener noreferrer me"
                                class="flex size-9 items-center justify-center rounded-lg bg-white/5 text-ink-300 transition hover:bg-brand-600 hover:text-white"
                                aria-label="{{ ucfirst($network) }}"
                            >
                                <span class="text-[11px] font-bold uppercase">{{ substr($network, 0, 2) }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>

                <div class="mt-6 flex items-center gap-2 text-xs text-ink-500">
                    <x-heroicon-o-map-pin class="size-4 shrink-0" />
                    <span>{{ config('hanbell.address') }}</span>
                </div>

                <a href="mailto:{{ config('hanbell.support_email') }}" class="mt-2 flex items-center gap-2 text-xs text-ink-500 transition hover:text-accent-300">
                    <x-heroicon-o-envelope class="size-4 shrink-0" />
                    <span>{{ config('hanbell.support_email') }}</span>
                </a>
            </div>

            {{-- Shop --}}
            <nav aria-labelledby="footer-shop">
                <h3 id="footer-shop" class="hb-eyebrow mb-4 text-white">{{ __('hanbell.footer.shop') }}</h3>
                <ul class="space-y-2.5 text-sm">
                    <li>
                        <a href="{{ route('storefront.shop') }}" wire:navigate class="transition hover:text-accent-300">
                            {{ __('hanbell.nav.all_products') }}
                        </a>
                    </li>
                    @foreach ($departments as $department)
                        <li>
                            <a href="{{ $department->publicUrl() }}" wire:navigate class="transition hover:text-accent-300">
                                {{ $department->name }}
                            </a>
                        </li>
                    @endforeach
                    <li>
                        <a href="{{ route('storefront.vendors.index') }}" wire:navigate class="transition hover:text-accent-300">
                            {{ __('hanbell.footer.brand_directory') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('storefront.shop', ['sort' => 'newest']) }}" wire:navigate class="transition hover:text-accent-300">
                            {{ __('hanbell.nav.new_arrivals') }}
                        </a>
                    </li>
                </ul>
            </nav>

            {{-- Help --}}
            <nav aria-labelledby="footer-help">
                <h3 id="footer-help" class="hb-eyebrow mb-4 text-white">{{ __('hanbell.footer.help') }}</h3>
                <ul class="space-y-2.5 text-sm">
                    <li>
                        <a href="{{ route('storefront.orders.track') }}" wire:navigate class="transition hover:text-accent-300">
                            {{ __('hanbell.footer.track_order') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('storefront.contact') }}" wire:navigate class="transition hover:text-accent-300">
                            {{ __('hanbell.footer.contact') }}
                        </a>
                    </li>
                    @foreach ($helpPages as $page)
                        <li>
                            <a href="{{ $page->publicUrl() }}" wire:navigate class="transition hover:text-accent-300">
                                {{ $page->title }}
                            </a>
                        </li>
                    @endforeach
                    <li>
                        <a href="{{ route('storefront.pages.show', ['slug' => 'size-guide']) }}" wire:navigate class="transition hover:text-accent-300">
                            {{ __('hanbell.footer.size_guide') }}
                        </a>
                    </li>
                </ul>
            </nav>

            {{-- Legal + company --}}
            <nav aria-labelledby="footer-legal">
                <h3 id="footer-legal" class="hb-eyebrow mb-4 text-white">{{ __('hanbell.footer.legal') }}</h3>
                <ul class="space-y-2.5 text-sm">
                    @foreach ($companyPages as $page)
                        <li>
                            <a href="{{ $page->publicUrl() }}" wire:navigate class="transition hover:text-accent-300">
                                {{ $page->title }}
                            </a>
                        </li>
                    @endforeach
                    @foreach ($policyPages as $page)
                        <li>
                            <a href="{{ $page->publicUrl() }}" wire:navigate class="transition hover:text-accent-300">
                                {{ $page->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </div>

    {{-- Payment rails actually configured on this install. Showing a provider
         that is not wired up would be a false promise at the point of payment. --}}
    @php
        $availableMethods = app(\App\Payments\PaymentGatewayManager::class)
            ->availableFor(config('hanbell.currency.default', 'NGN'));
    @endphp

    @if ($availableMethods->isNotEmpty())
        <div class="border-t border-white/10">
            <div class="hb-container flex flex-wrap items-center justify-between gap-4 py-6">
                <span class="text-xs font-semibold uppercase tracking-wider text-ink-500">
                    {{ __('hanbell.footer.payments') }}
                </span>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($availableMethods as $gateway)
                        <span class="rounded-md bg-white/5 px-2.5 py-1.5 text-xs font-semibold text-ink-300">
                            {{ $gateway->label() }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="border-t border-white/10">
        <div class="hb-container flex flex-col gap-3 py-6 text-xs text-ink-500 sm:flex-row sm:items-center sm:justify-between">
            <p>
                &copy; {{ now()->year }} {{ config('hanbell.name') }}, a product of
                {{ config('hanbell.operator.name') }}. {{ __('hanbell.footer.rights') }}
            </p>
            <p class="flex items-center gap-1.5">
                <span class="size-1.5 rounded-full bg-brand-500" aria-hidden="true"></span>
                {{ __('hanbell.footer.made_in_nigeria') }}
            </p>
        </div>

        @if (config('hanbell.demo.show_disclosure'))
            {{-- Honesty: the seeded brands and products are invented, and a
                 shopper should never be left thinking otherwise. --}}
            <div class="border-t border-white/5">
                <div class="hb-container py-4">
                    <p class="text-[11px] leading-relaxed text-ink-600">
                        {{ __('hanbell.footer.demo_disclosure') }}
                    </p>
                </div>
            </div>
        @endif
    </div>
</footer>

{{-- Mobile bottom tab bar. Hidden for administrators, who have no bag. --}}
@unless (auth()->user()?->isAdmin())
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-ink-200 bg-white pb-[env(safe-area-inset-bottom)] sm:hidden" aria-label="{{ __('hanbell.nav.menu') }}">
        <div class="grid grid-cols-5">
            @php
                $tabs = [
                    ['route' => 'storefront.home', 'icon' => 'home', 'label' => __('hanbell.nav.home')],
                    ['route' => 'storefront.shop', 'icon' => 'squares-2x2', 'label' => __('hanbell.nav.shop')],
                    ['route' => 'storefront.search', 'icon' => 'magnifying-glass', 'label' => __('hanbell.nav.search')],
                    ['route' => 'storefront.wishlist', 'icon' => 'heart', 'label' => __('hanbell.nav.wishlist')],
                    ['route' => 'storefront.cart', 'icon' => 'shopping-bag', 'label' => __('hanbell.nav.bag')],
                ];
            @endphp

            @foreach ($tabs as $tab)
                @php
                    $active = request()->routeIs($tab['route']) || request()->routeIs($tab['route'].'.*');
                @endphp

                <a
                    href="{{ route($tab['route']) }}"
                    wire:navigate
                    class="flex flex-col items-center gap-1 py-2.5 text-[10px] font-semibold transition {{ $active ? 'text-brand-700' : 'text-ink-500' }}"
                    @if ($active) aria-current="page" @endif
                >
                    <x-dynamic-component :component="'heroicon-'.($active ? 's' : 'o').'-'.$tab['icon']" class="size-5" />
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>
    </nav>
@endunless
