<div class="hb-container py-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink-950 sm:text-3xl">{{ __('hanbell.nav.wishlist') }}</h1>

    @include('partials.account-nav')

    <div class="mt-6">
        @if ($items->isEmpty())
            <x-ui.card padding="none">
                <x-ui.empty-state
                    icon="heart"
                    :title="__('hanbell.cart.empty_wishlist')"
                    :message="__('hanbell.cart.empty_hint')"
                    :action-label="__('hanbell.cart.start_shopping')"
                    :action-href="route('storefront.shop')"
                />
            </x-ui.card>
        @else
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
                @foreach ($items as $item)
                    @if ($item->product)
                        <div>
                            <x-product.card :product="$item->product" />

                            <div class="mt-2 flex gap-2">
                                <x-ui.button wire:click="moveToBag({{ $item->id }})" variant="outline" size="sm" block>
                                    {{ __('hanbell.cart.move_to_bag') }}
                                </x-ui.button>

                                <x-ui.button
                                    wire:click="remove({{ $item->id }})"
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label="{{ __('hanbell.common.remove') }}"
                                >
                                    <x-heroicon-o-trash class="size-4" />
                                </x-ui.button>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
