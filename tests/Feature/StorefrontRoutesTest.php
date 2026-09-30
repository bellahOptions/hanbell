<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Route smoke tests.
 *
 * Every page in the application is requested as the role that should be able to
 * see it. A new screen that throws, or a view that was never created, fails here
 * rather than in front of a user — the failure mode these catch is exactly the
 * "View [x] not found" that a missing Blade file produces.
 *
 * These assert status codes, not layout. Visual correctness is verified by
 * looking at the pages; this is the safety net underneath that.
 */
class StorefrontRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * A minimal fixture, not DatabaseSeeder::run().
         *
         * The demo seeder creates ~72 products, ~676 variants and ~750 inventory
         * rows. Running that inside every test (RefreshDatabase wraps each test
         * in a transaction) exhausts the default 512M memory limit before the
         * assertions run. Three products exercise exactly the same code paths.
         */
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seedMinimalCatalogue();
        $this->seedDemoAccounts();
    }

    private function seedDemoAccounts(): void
    {
        $admin = User::create([
            'name' => 'Test Administrator',
            'email' => 'admin@hanbellshop.demo',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $customer = User::create([
            'name' => 'Ada Okonkwo',
            'email' => 'customer@hanbellshop.demo',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $customer->assignRole('customer');

        $other = User::create([
            'name' => 'Tunde Bakare',
            'email' => 'secured@hanbellshop.demo',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $other->assignRole('customer');
    }

    private function seedMinimalCatalogue(): void
    {
        $department = \App\Models\Department::create(['name' => 'Women', 'gender' => 'women', 'is_active' => true]);
        $category = \App\Models\Category::create([
            'department_id' => $department->id,
            'name' => 'Dresses',
            'is_active' => true,
        ]);

        $vendorOwner = User::create([
            'name' => 'Brand Owner',
            'email' => 'owner@hanbellshop.demo',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $vendorOwner->assignRole('vendor');

        $vendor = Vendor::create([
            'owner_id' => $vendorOwner->id,
            'name' => 'Test Atelier',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'description' => 'A test brand.',
            'status' => \App\Enums\VendorStatus::Approved,
            'approved_at' => now(),
        ]);

        foreach (['Adire Wrap Dress', 'Ankara Midi Dress', 'Aso-Oke Gown'] as $index => $name) {
            $product = Product::create([
                'vendor_id' => $vendor->id,
                'category_id' => $category->id,
                'department_id' => $department->id,
                'name' => $name,
                'summary' => 'A test product.',
                'description' => 'A test product description.',
                'price_minor' => 4_500_000,
                'compare_at_price_minor' => $index === 0 ? 6_000_000 : null,
                'currency' => 'NGN',
                'status' => \App\Enums\ProductStatus::Published,
                'published_at' => now(),
                'gender' => 'women',
                'made_in_city' => 'Lagos',
            ]);

            $variant = \App\Models\ProductVariant::create([
                'product_id' => $product->id,
                'name' => 'M / Indigo',
                'size' => 'M',
                'color' => 'Indigo',
                'is_active' => true,
            ]);

            \App\Models\Inventory::create([
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => 10,
                'quantity_reserved' => 0,
            ]);

            /*
             * A product-level inventory row (variant_id null) as well.
             *
             * `availableQuantity()` sums variant stock when variants exist, but
             * the checkout path reads the product-level row for the
             * product-with-no-variant case. Seeding only the variant row leaves
             * the product unknowable as "in stock" to that path, which surfaces
             * as a confusing InsufficientStockException in an unrelated test.
             */
            \App\Models\Inventory::create([
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity_on_hand' => 10,
                'quantity_reserved' => 0,
            ]);

            \App\Models\ProductMedia::create([
                'product_id' => $product->id,
                'path' => 'https://images.unsplash.com/photo-test?auto=format&fit=crop&w=900&q=80',
                'disk' => 'public',
                'alt_text' => $name,
                'is_primary' => true,
                'credit_name' => 'Test Photographer',
                'credit_url' => 'https://unsplash.com/@test',
            ]);
        }

        // One publication and one campaign, so the ad and CMS paths are real.
        \App\Models\Page::create([
            'title' => 'Returns Policy',
            'slug' => 'returns-policy',
            'content' => '<p>Returns are accepted within 14 days.</p>',
            'group' => 'policy',
            'status' => \App\Enums\PageStatus::Published,
            'published_at' => now(),
            'show_in_footer' => true,
        ]);

        foreach (\App\Enums\AdPlacementKey::cases() as $key) {
            \App\Models\AdPlacement::create([
                'key' => $key->value,
                'label' => $key->label(),
                'recommended_size' => $key->recommendedSize(),
                'aspect_class' => $key->aspectClass(),
                'max_creatives' => $key->maxCreatives(),
                'is_active' => true,
            ]);
        }

        $advertiser = \App\Models\Advertiser::create(['name' => 'Test Advertiser']);

        $campaign = \App\Models\AdCampaign::create([
            'advertiser_id' => $advertiser->id,
            'name' => 'Test Campaign',
            'status' => \App\Enums\AdCampaignStatus::Active,
            'pricing_model' => \App\Enums\AdPricingModel::Cpm,
            'budget_minor' => 50_000_000,
            'bid_minor' => 50_000,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $campaign->placements()->attach(
            \App\Models\AdPlacement::where('key', \App\Enums\AdPlacementKey::HomeHero->value)->value('id'),
        );

        \App\Models\AdCreative::create([
            'ad_campaign_id' => $campaign->id,
            'name' => 'Test Creative',
            'headline' => 'Test Atelier',
            'destination_url' => '/shop',
            'destination_type' => 'internal',
            'is_active' => true,
        ]);
    }

    /* ------------------------------------------------------------------ *
     * Public storefront
     * ------------------------------------------------------------------ */

    #[Test]
    public function public_pages_load(): void
    {
        $routes = [
            '/',
            '/shop',
            '/search?q=dress',
            '/brands',
            '/cart',
            '/wishlist',
            '/contact',
            '/order-tracking',
            '/sell-with-us',
            '/login',
            '/register',
            '/forgot-password',
            '/pages/about',
            '/pages/terms-of-service',
            '/pages/privacy-policy',
            '/pages/returns-policy',
            '/pages/shipping-policy',
            '/pages/cookie-policy',
            '/pages/acceptable-use',
            '/pages/size-guide',
            '/pages/faq',
            '/pages/sell-with-us',
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertOk();
        }
    }

    #[Test]
    public function a_page_that_does_not_exist_yet_renders_a_placeholder_not_a_404(): void
    {
        // Footer policy links must resolve even before an administrator has
        // written the page, otherwise the footer is full of dead ends.
        $this->get('/pages/a-page-nobody-has-written')->assertOk();
    }

    #[Test]
    public function machine_readable_endpoints_load(): void
    {
        $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->get('/llms.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->get('/llms-full.txt')->assertOk();
        $this->get('/sitemap.xml')->assertOk();
        $this->get('/sitemap-products.xml')->assertOk();
        $this->get('/sitemap-categories.xml')->assertOk();
        $this->get('/sitemap-vendors.xml')->assertOk();
        $this->get('/sitemap-pages.xml')->assertOk();
    }

    #[Test]
    public function robots_txt_points_at_the_sitemap_and_welcomes_answer_engines(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertSee('Sitemap:', false);
        $response->assertSee('GPTBot', false);
        $response->assertSee('ClaudeBot', false);
        $response->assertSee('Disallow: /admin', false);
    }

    #[Test]
    public function llms_txt_describes_the_catalogue(): void
    {
        $response = $this->get('/llms.txt');

        $response->assertSee('HanbellShop', false);
        $response->assertSee('Nigerian', false);
    }

    /* ------------------------------------------------------------------ *
     * Tokenised catalogue URLs
     * ------------------------------------------------------------------ */

    #[Test]
    public function product_pages_load_via_a_tokenised_url(): void
    {
        $product = Product::published()->firstOrFail();

        $this->get($product->publicUrl())->assertOk()->assertSee($product->name, false);
    }

    #[Test]
    public function a_tampered_product_token_is_a_404_not_a_500(): void
    {
        $product = Product::published()->firstOrFail();
        $token = $product->urlToken();

        // Flip the signature so verification fails.
        $tampered = substr($token, 0, -4).'AAAA';

        $this->get('/product/'.$tampered)->assertNotFound();
    }

    #[Test]
    public function a_token_for_another_model_type_is_rejected(): void
    {
        // A vendor token must not open a product route — the type is bound into
        // the signature precisely to prevent this confusion.
        $vendor = Vendor::approved()->firstOrFail();

        $this->get('/product/'.$vendor->urlToken())->assertNotFound();
    }

    #[Test]
    public function category_and_vendor_pages_load_via_tokenised_urls(): void
    {
        $category = \App\Models\Category::query()->active()->firstOrFail();
        $vendor = Vendor::approved()->firstOrFail();

        $this->get($category->publicUrl())->assertOk();
        $this->get($vendor->publicUrl())->assertOk();
    }

    /* ------------------------------------------------------------------ *
     * Authentication
     * ------------------------------------------------------------------ */

    #[Test]
    public function an_administrator_reaches_the_admin_panel(): void
    {
        $admin = User::where('email', 'admin@hanbellshop.demo')->firstOrFail();

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    /**
     * Every admin screen, not just the dashboard.
     *
     * A missing Blade view produces "View [x] not found" at runtime rather than
     * a compile error, so an unvisited screen can sit broken indefinitely. This
     * walks the whole panel.
     */
    #[Test]
    public function every_admin_screen_loads(): void
    {
        $admin = User::where('email', 'admin@hanbellshop.demo')->firstOrFail();
        $this->actingAs($admin);

        $product = Product::published()->firstOrFail();
        $vendor = Vendor::approved()->firstOrFail();

        $routes = [
            '/admin',
            '/admin/products',
            '/admin/products/create',
            // Models route by uuid (HasUuid::getRouteKeyName), never by a
            // sequential id — so the URL is built from the model, which is how
            // the admin tables generate their links.
            route('admin.products.edit', $product, absolute: false),
            '/admin/categories',
            '/admin/departments',
            '/admin/brands',
            '/admin/attributes',
            '/admin/tags',
            '/admin/inventory',
            '/admin/vendors',
            route('admin.vendors.show', $vendor, absolute: false),
            '/admin/orders',
            '/admin/payments',
            '/admin/customers',
            route('admin.customers.show', $admin, absolute: false),
            '/admin/advertising',
            '/admin/advertising/placements',
            '/admin/advertising/advertisers',
            '/admin/advertising/reports',
            '/admin/pages',
            '/admin/pages/create',
            '/admin/reviews',
            '/admin/newsletter',
            '/admin/seo',
            '/admin/seo/redirects',
            '/admin/settings',
            '/admin/audit',
        ];

        foreach ($routes as $route) {
            // Asserted one at a time with the path in the message, so a failure
            // names the broken screen instead of just reporting "404".
            $response = $this->get($route);

            $this->assertSame(
                200,
                $response->getStatusCode(),
                "Admin screen [{$route}] did not return 200.",
            );
        }
    }

    #[Test]
    public function an_administrator_is_kept_out_of_the_storefront_shopping_area(): void
    {
        $admin = User::where('email', 'admin@hanbellshop.demo')->firstOrFail();

        // Admins deliberately have no cart, wishlist or checkout.
        $this->actingAs($admin)->get('/cart')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->get('/wishlist')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->get('/checkout')->assertRedirect(route('admin.dashboard'));
    }

    #[Test]
    public function a_customer_can_reach_their_account_pages(): void
    {
        $customer = User::where('email', 'customer@hanbellshop.demo')->firstOrFail();

        foreach ([
            '/account',
            '/account/orders',
            '/account/addresses',
            '/account/security',
            '/account/wishlist',
        ] as $route) {
            $this->actingAs($customer)->get($route)->assertOk();
        }
    }

    #[Test]
    public function a_customer_cannot_reach_the_admin_panel(): void
    {
        $customer = User::where('email', 'customer@hanbellshop.demo')->firstOrFail();

        $this->actingAs($customer)->get('/admin')->assertForbidden();
    }

    #[Test]
    public function a_guest_is_sent_to_sign_in(): void
    {
        $this->get('/account')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect();
    }

    #[Test]
    public function a_vendor_reaches_the_vendor_panel(): void
    {
        $vendorUser = Vendor::approved()->firstOrFail()->owner;

        $this->assertNotNull($vendorUser, 'A seeded vendor should have an owner account.');

        foreach ([
            '/vendor',
            '/vendor/products',
            '/vendor/inventory',
            '/vendor/orders',
            '/vendor/profile',
        ] as $route) {
            $this->actingAs($vendorUser)->get($route)->assertOk();
        }
    }

    /* ------------------------------------------------------------------ *
     * Order access control
     * ------------------------------------------------------------------ */

    #[Test]
    public function an_order_belonging_to_another_user_is_a_404(): void
    {
        $customer = User::where('email', 'customer@hanbellshop.demo')->firstOrFail();
        $other = User::where('email', 'secured@hanbellshop.demo')->firstOrFail();

        // OrderFactory is not defined in this project, so the order is built
        // from the seeded catalogue rather than a factory stub.
        $product = Product::published()->firstOrFail();

        $order = app(\App\Services\Commerce\OrderService::class)->createFromCart(
            cart: tap(app(\App\Services\Commerce\CartService::class), function ($carts) use ($customer, $product) {
                $this->actingAs($customer);
                $carts->add($product, null, 1);
            })->current(),
            shipping: [
                'recipient_name' => 'Test Recipient',
                'phone' => '+2348000000000',
                'line1' => '1 Test Street',
                'city' => 'Lagos',
                'state' => 'Lagos',
            ],
            contact: ['name' => 'Test', 'email' => 'test@hanbellshop.demo', 'phone' => '+2348000000000'],
            user: $customer,
        );

        // The owner can open it.
        $this->actingAs($customer)->get($order->publicUrl())->assertOk();

        // A different signed-in account cannot.
        $this->actingAs($other)->get($order->publicUrl())->assertNotFound();
    }

    /* ------------------------------------------------------------------ *
     * Security headers
     * ------------------------------------------------------------------ */

    #[Test]
    public function security_headers_are_present(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $this->assertStringContainsString(
            "frame-ancestors 'none'",
            (string) $response->headers->get('Content-Security-Policy'),
        );
    }

    /* ------------------------------------------------------------------ *
     * SEO
     * ------------------------------------------------------------------ */

    #[Test]
    public function the_homepage_emits_structured_data_for_search_and_answer_engines(): void
    {
        $response = $this->get('/');

        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type":"Organization"', false);
        $response->assertSee('"@type":"WebSite"', false);
        $response->assertSee('"@type":"FAQPage"', false);
        $response->assertSee('rel="canonical"', false);
        $response->assertSee('hreflang="ha"', false);
    }

    #[Test]
    public function a_product_page_emits_a_product_schema_with_an_offer(): void
    {
        $product = Product::published()->firstOrFail();

        $response = $this->get($product->publicUrl());

        $response->assertSee('"@type":"Product"', false);
        $response->assertSee('"@type":"Offer"', false);
        $response->assertSee('priceCurrency', false);
    }

    #[Test]
    public function a_filtered_listing_is_not_indexable(): void
    {
        // A filtered view is a duplicate of its parent page; letting crawlers
        // index every combination wastes crawl budget on near-identical URLs.
        $response = $this->get('/shop?sort=price_low&stock=1');

        $response->assertSee('noindex', false);
    }

    /* ------------------------------------------------------------------ *
     * Localisation
     * ------------------------------------------------------------------ */

    #[Test]
    public function every_configured_locale_has_a_complete_translation_file(): void
    {
        $english = $this->flattenKeys(require lang_path('en/hanbell.php'));

        foreach (array_keys(config('hanbell.locales')) as $locale) {
            $path = lang_path("{$locale}/hanbell.php");

            $this->assertFileExists($path, "Missing translation file for locale [{$locale}].");

            $translated = $this->flattenKeys(require $path);

            $missing = array_diff($english, $translated);
            $extra = array_diff($translated, $english);

            $this->assertSame([], array_values($missing), "Locale [{$locale}] is missing keys.");
            $this->assertSame([], array_values($extra), "Locale [{$locale}] has keys English does not.");
        }
    }

    #[Test]
    public function switching_language_persists_and_renders(): void
    {
        $this->get('/locale/yo')->assertRedirect();

        $this->get('/')->assertOk();
    }

    #[Test]
    public function an_unknown_locale_is_rejected(): void
    {
        $this->get('/locale/xx')->assertNotFound();
    }

    /* ------------------------------------------------------------------ *
     * Advertising
     * ------------------------------------------------------------------ */

    #[Test]
    public function the_homepage_serves_a_live_ad_creative(): void
    {
        $response = $this->get('/');

        $response->assertSee('data-ad-creative', false);
        $response->assertSee('data-ad-impression-url', false);
    }

    #[Test]
    public function an_ad_click_to_an_unsafe_destination_is_refused(): void
    {
        $creative = \App\Models\AdCreative::firstOrFail();
        $creative->forceFill(['destination_url' => 'javascript:alert(1)'])->save();

        // An unvalidated destination would be an open redirect; the controller
        // re-checks at click time even though the form validates on save.
        $this->get(route('ads.click', ['creative' => $creative->urlToken()]))->assertNotFound();
    }

    #[Test]
    public function an_ad_click_to_an_allowed_internal_path_redirects(): void
    {
        $creative = \App\Models\AdCreative::firstOrFail();
        $creative->forceFill(['destination_url' => '/shop'])->save();

        $this->get(route('ads.click', ['creative' => $creative->urlToken()]))->assertRedirect('/shop');
    }

    /* ------------------------------------------------------------------ *
     * Payments
     * ------------------------------------------------------------------ */

    #[Test]
    public function an_unsigned_webhook_is_rejected(): void
    {
        // Without a valid signature anyone could POST "paid" and get free goods.
        $this->postJson('/webhooks/paystack', ['event' => 'charge.success'])
            ->assertUnauthorized();
    }

    #[Test]
    public function a_payment_callback_with_an_unknown_reference_does_not_crash(): void
    {
        $this->get('/checkout/callback/paystack?reference=HB-NOPE')
            ->assertRedirect();
    }

    /* ------------------------------------------------------------------ *
     * Helpers
     * ------------------------------------------------------------------ */

    /** @return array<int,string> */
    private function flattenKeys(array $array, string $prefix = ''): array
    {
        $keys = [];

        foreach ($array as $key => $value) {
            $full = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $keys = array_merge($keys, $this->flattenKeys($value, $full));

                continue;
            }

            $keys[] = $full;
        }

        return $keys;
    }
}
