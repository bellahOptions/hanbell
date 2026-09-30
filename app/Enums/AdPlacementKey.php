<?php

namespace App\Enums;

/**
 * Ad slots are a closed set on purpose.
 *
 * Every case here has exactly one real Blade call site
 * (`<x-ads.slot :placement="..."/>`). A free-form string column would let a
 * typo render nothing forever with no error anywhere; an enum makes that a
 * compile-time mistake instead. Adding a slot means touching this enum, the
 * seeder and the call site — which is the point.
 */
enum AdPlacementKey: string
{
    case HomeHero = 'home_hero';
    case HomeStrip = 'home_strip';
    case HomeMidBanner = 'home_mid_banner';
    case HomeSidebar = 'home_sidebar';
    case CategoryTopBanner = 'category_top_banner';
    case CategorySidebar = 'category_sidebar';
    case ShopInline = 'shop_inline';
    case SearchTopBanner = 'search_top_banner';
    case ProductSidebar = 'product_sidebar';
    case ProductBelowDetails = 'product_below_details';
    case CartSidebar = 'cart_sidebar';
    case CheckoutSidebar = 'checkout_sidebar';
    case OrderConfirmation = 'order_confirmation';
    case AccountSidebar = 'account_sidebar';
    case FooterBanner = 'footer_banner';

    public function label(): string
    {
        return match ($this) {
            self::HomeHero => 'Homepage hero',
            self::HomeStrip => 'Homepage promo strip',
            self::HomeMidBanner => 'Homepage mid banner',
            self::HomeSidebar => 'Homepage sidebar rail',
            self::CategoryTopBanner => 'Category page top',
            self::CategorySidebar => 'Category page sidebar',
            self::ShopInline => 'Shop grid inline',
            self::SearchTopBanner => 'Search results top',
            self::ProductSidebar => 'Product page sidebar',
            self::ProductBelowDetails => 'Product page below details',
            self::CartSidebar => 'Cart sidebar',
            self::CheckoutSidebar => 'Checkout sidebar',
            self::OrderConfirmation => 'Order confirmation',
            self::AccountSidebar => 'Account sidebar',
            self::FooterBanner => 'Footer banner',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::HomeHero => 'The rotating hero at the top of the homepage. Highest traffic slot on the site.',
            self::HomeStrip => 'The thin promotional strip directly beneath the hero.',
            self::HomeMidBanner => 'A wide banner between homepage product rails.',
            self::HomeSidebar => 'Tall vertical rail on the right of the homepage.',
            self::CategoryTopBanner => 'Leaderboard above the category product grid.',
            self::CategorySidebar => 'Vertical rail beside category filters.',
            self::ShopInline => 'Injected between rows of the shop grid.',
            self::SearchTopBanner => 'Shown above search results — high intent.',
            self::ProductSidebar => 'Beside the product detail column.',
            self::ProductBelowDetails => 'Full-width banner under the product description.',
            self::CartSidebar => 'Beside the cart summary.',
            self::CheckoutSidebar => 'Beside the checkout summary.',
            self::OrderConfirmation => 'Shown to a shopper who has just paid.',
            self::AccountSidebar => 'In the customer account area.',
            self::FooterBanner => 'Wide banner just above the site footer.',
        };
    }

    /** Recommended creative dimensions, shown to admins. */
    public function recommendedSize(): string
    {
        return match ($this) {
            self::HomeHero => '1600 × 720',
            self::HomeStrip => '1600 × 160',
            self::HomeMidBanner, self::CategoryTopBanner, self::SearchTopBanner, self::FooterBanner => '1280 × 320',
            self::HomeSidebar, self::CategorySidebar, self::ProductSidebar, self::CartSidebar, self::CheckoutSidebar, self::AccountSidebar => '480 × 720',
            self::ShopInline, self::ProductBelowDetails, self::OrderConfirmation => '970 × 250',
        };
    }

    /** Tailwind aspect-ratio class applied to the creative frame. */
    public function aspectClass(): string
    {
        return match ($this) {
            self::HomeHero => 'aspect-[20/9]',
            self::HomeStrip => 'aspect-[10/1]',
            self::HomeMidBanner, self::CategoryTopBanner, self::SearchTopBanner, self::FooterBanner => 'aspect-[4/1]',
            self::HomeSidebar, self::CategorySidebar, self::ProductSidebar, self::CartSidebar, self::CheckoutSidebar, self::AccountSidebar => 'aspect-[2/3]',
            self::ShopInline, self::ProductBelowDetails, self::OrderConfirmation => 'aspect-[97/25]',
        };
    }

    /** How many creatives may show in this slot at once. */
    public function maxCreatives(): int
    {
        return match ($this) {
            self::HomeHero => 4,
            self::HomeSidebar => 2,
            self::ShopInline => 3,
            default => 1,
        };
    }

    /** Slots rendered as a horizontal rail/carousel rather than a single block. */
    public function isRail(): bool
    {
        return in_array($this, [self::HomeStrip, self::HomeSidebar, self::ShopInline], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<string,string> value => label, for admin selects. */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
