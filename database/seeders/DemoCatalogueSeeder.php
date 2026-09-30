<?php

namespace Database\Seeders;

use App\Enums\AdCampaignStatus;
use App\Enums\AdPlacementKey;
use App\Enums\AdPricingModel;
use App\Enums\ProductStatus;
use App\Enums\TwoFactorMethod;
use App\Enums\VendorStatus;
use App\Models\AdCampaign;
use App\Models\AdCreative;
use App\Models\AdPlacement;
use App\Models\Advertiser;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo catalogue.
 *
 * Everything created here is FICTIONAL. The brand names, product names, prices
 * and stock figures are invented to demonstrate the platform; the photography
 * is real Unsplash work, hotlinked with attribution (see
 * database/data/unsplash_images.php for the licence rationale). The storefront
 * shows a disclosure to this effect, so a visitor is never misled into thinking
 * these are real listings from real Nigerian businesses.
 */
class DemoCatalogueSeeder extends Seeder
{
    /** @var array<string,array<int,array<string,mixed>>> */
    private array $images = [];

    public function run(): void
    {
        $this->images = require database_path('data/unsplash_images.php');

        $this->seedSettings();
        $this->seedAttributes();
        $departments = $this->seedDepartments();
        $categories = $this->seedCategories($departments);
        $vendors = $this->seedVendors();
        $this->seedProducts($vendors, $categories);
        $this->seedAdPlacements();
        $this->seedAdvertising($vendors);
    }

    /* ------------------------------------------------------------------ *
     * Settings
     * ------------------------------------------------------------------ */

    private function seedSettings(): void
    {
        $rows = [
            ['group' => 'store', 'key' => 'store.name', 'value' => config('hanbell.name'), 'type' => 'string', 'label' => 'Store name', 'is_public' => true, 'position' => 1],
            ['group' => 'store', 'key' => 'store.tagline', 'value' => config('hanbell.tagline'), 'type' => 'string', 'label' => 'Tagline', 'is_public' => true, 'position' => 2],
            ['group' => 'store', 'key' => 'store.support_email', 'value' => config('hanbell.support_email'), 'type' => 'string', 'label' => 'Support email', 'is_public' => true, 'position' => 3],
            ['group' => 'store', 'key' => 'store.support_phone', 'value' => config('hanbell.support_phone'), 'type' => 'string', 'label' => 'Support phone', 'is_public' => true, 'position' => 4],
            ['group' => 'store', 'key' => 'store.whatsapp', 'value' => config('hanbell.whatsapp'), 'type' => 'string', 'label' => 'WhatsApp number', 'is_public' => true, 'position' => 5],

            /*
             * The operating entity.
             *
             * HanbellShop is a trading name of Bellah Options, and every
             * financial and legal document names that entity. Editable here so
             * an administrator can add the RC number and TIN without a deploy —
             * those are left blank on purpose, because a Nigerian invoice that
             * carries an invented registration number is worse than one that
             * carries none.
             */
            ['group' => 'company', 'key' => 'company.operator_name', 'value' => config('hanbell.operator.name'), 'type' => 'string', 'label' => 'Operating entity', 'description' => 'The legal entity that issues invoices and holds the customer contract.', 'is_public' => true, 'position' => 1],
            ['group' => 'company', 'key' => 'company.registration_number', 'value' => '', 'type' => 'string', 'label' => 'RC number', 'description' => 'Corporate Affairs Commission registration number. Omitted from documents while blank.', 'position' => 2],
            ['group' => 'company', 'key' => 'company.tin', 'value' => '', 'type' => 'string', 'label' => 'TIN', 'description' => 'Tax Identification Number. Omitted from documents while blank.', 'position' => 3],
            ['group' => 'company', 'key' => 'company.vat_number', 'value' => '', 'type' => 'string', 'label' => 'VAT number', 'position' => 4],
            ['group' => 'company', 'key' => 'company.address_line', 'value' => '', 'type' => 'string', 'label' => 'Registered address', 'position' => 5],
            ['group' => 'company', 'key' => 'company.city', 'value' => '', 'type' => 'string', 'label' => 'City', 'position' => 6],
            ['group' => 'company', 'key' => 'company.state', 'value' => '', 'type' => 'string', 'label' => 'State', 'position' => 7],
            ['group' => 'company', 'key' => 'company.website', 'value' => '', 'type' => 'string', 'label' => 'Company website', 'position' => 8],

            ['group' => 'social', 'key' => 'social.instagram', 'value' => 'https://instagram.com/hanbellshop', 'type' => 'string', 'label' => 'Instagram URL', 'is_public' => true, 'position' => 1],
            ['group' => 'social', 'key' => 'social.twitter', 'value' => 'https://x.com/hanbellshop', 'type' => 'string', 'label' => 'X (Twitter) URL', 'is_public' => true, 'position' => 2],
            ['group' => 'social', 'key' => 'social.facebook', 'value' => 'https://facebook.com/hanbellshop', 'type' => 'string', 'label' => 'Facebook URL', 'is_public' => true, 'position' => 3],

            ['group' => 'seo', 'key' => 'seo.default_title', 'value' => config('hanbell.name').' — '.config('hanbell.tagline'), 'type' => 'string', 'label' => 'Default page title', 'position' => 1],
            ['group' => 'seo', 'key' => 'seo.default_description', 'value' => config('hanbell.description'), 'type' => 'text', 'label' => 'Default meta description', 'position' => 2],
            ['group' => 'seo', 'key' => 'seo.twitter_handle', 'value' => config('hanbell.seo.twitter_handle'), 'type' => 'string', 'label' => 'Twitter/X handle', 'position' => 3],
            ['group' => 'seo', 'key' => 'seo.robots_extra', 'value' => '', 'type' => 'text', 'label' => 'Extra robots.txt rules', 'description' => 'Appended verbatim to robots.txt.', 'position' => 4],
            ['group' => 'seo', 'key' => 'seo.llms_enabled', 'value' => '1', 'type' => 'boolean', 'label' => 'Publish llms.txt', 'description' => 'Allow language models to read a structured summary of the catalogue.', 'position' => 5],

            ['group' => 'llm', 'key' => 'llm.site_summary', 'value' => 'HanbellShop is a multi-vendor ecommerce marketplace for fashion made by indigenous Nigerian brands and independent creators. It sells quality pieces at fair, affordable prices and accepts both local and international payment methods.', 'type' => 'text', 'label' => 'Site summary for language models', 'description' => 'The factual paragraph published at /llms.txt.', 'position' => 1],

            ['group' => 'commerce', 'key' => 'commerce.flat_shipping', 'value' => (string) config('hanbell.shipping.flat_minor'), 'type' => 'integer', 'label' => 'Flat delivery fee (kobo)', 'position' => 1],
            ['group' => 'commerce', 'key' => 'commerce.free_shipping_threshold', 'value' => (string) config('hanbell.shipping.free_threshold_minor'), 'type' => 'integer', 'label' => 'Free delivery above (kobo)', 'position' => 2],
            ['group' => 'commerce', 'key' => 'commerce.commission_percent', 'value' => (string) config('hanbell.commission.default_percent'), 'type' => 'string', 'label' => 'Default commission %', 'position' => 3],
        ];

        foreach ($rows as $row) {
            Setting::updateOrCreate(['key' => $row['key']], $row);
        }
    }

    /* ------------------------------------------------------------------ *
     * Attributes
     * ------------------------------------------------------------------ */

    private function seedAttributes(): void
    {
        $definitions = [
            'Size' => ['type' => 'select', 'variant' => true, 'values' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'One size']],
            'Colour' => ['type' => 'select', 'variant' => true, 'values' => ['Indigo', 'Ochre', 'Black', 'Ivory', 'Emerald', 'Burgundy', 'Sand']],
            'Fit' => ['type' => 'select', 'variant' => false, 'values' => ['Slim', 'Regular', 'Relaxed', 'Oversized']],
            'Occasion' => ['type' => 'multiselect', 'variant' => false, 'values' => ['Everyday', 'Wedding', 'Work', 'Festive', 'Evening']],
            'Fabric' => ['type' => 'select', 'variant' => false, 'values' => ['Ankara', 'Aso-oke', 'Adire', 'Lace', 'Cotton', 'Silk', 'Leather']],
        ];

        foreach ($definitions as $name => $definition) {
            $attribute = Attribute::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'type' => $definition['type'],
                    'is_filterable' => true,
                    'is_variant_axis' => $definition['variant'],
                ],
            );

            foreach ($definition['values'] as $index => $value) {
                AttributeValue::updateOrCreate(
                    ['attribute_id' => $attribute->id, 'slug' => Str::slug($value)],
                    ['value' => $value, 'position' => $index],
                );
            }
        }
    }

    /* ------------------------------------------------------------------ *
     * Departments & categories
     * ------------------------------------------------------------------ */

    /** @return array<string,Department> */
    private function seedDepartments(): array
    {
        $definitions = [
            'Women' => ['gender' => 'women', 'icon' => 'sparkles', 'description' => 'Dresses, tops and tailoring made by Nigerian designers.'],
            'Men' => ['gender' => 'men', 'icon' => 'user', 'description' => 'Agbada, kaftans, shirting and trousers from Nigerian ateliers.'],
            'Kids' => ['gender' => 'kids', 'icon' => 'face-smile', 'description' => 'Comfortable, well-made pieces for children.'],
            'Accessories' => ['gender' => 'unisex', 'icon' => 'briefcase', 'description' => 'Bags, jewellery, headwraps and finishing touches.'],
        ];

        $departments = [];

        foreach ($definitions as $name => $definition) {
            $departments[$name] = Department::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'gender' => $definition['gender'],
                    'icon' => $definition['icon'],
                    'description' => $definition['description'],
                    'is_active' => true,
                    'position' => array_search($name, array_keys($definitions), true),
                    'meta_title' => $name.'\'s fashion from Nigerian brands',
                    'meta_description' => $definition['description'],
                ],
            );
        }

        return $departments;
    }

    /**
     * @param  array<string,Department>  $departments
     * @return array<string,Category>
     */
    private function seedCategories(array $departments): array
    {
        $definitions = [
            'Dresses' => ['Women', 'dresses', 'Day dresses, occasion dresses and everything between.'],
            'Tops' => ['Women', 'tops', 'Blouses, shirts and statement tops.'],
            'Skirts' => ['Women', 'skirts', 'Wrap, pencil and maxi skirts.'],
            'Trousers' => ['Women', 'trousers', 'Tailored trousers and wide-leg cuts.'],
            'Agbada' => ['Men', 'agbada', 'Three-piece agbada sets, made to measure.'],
            'Kaftans' => ['Men', 'kaftans', 'Kaftans and tunics for everyday and occasion.'],
            'Shirts' => ['Men', 'shirts', 'Ankara and plain shirting.'],
            'Menswear' => ['Men', 'menswear', 'Suits, jackets and complete looks.'],
            'Shoes' => ['Accessories', 'shoes', 'Heels, loafers, sandals and sneakers.'],
            'Bags' => ['Accessories', 'bags', 'Totes, clutches and shoulder bags.'],
            'Jewellery' => ['Accessories', 'jewellery', 'Beadwork, brass and statement pieces.'],
            'Headwraps' => ['Accessories', 'headwraps', 'Gele and headwraps in aso-oke and ankara.'],
            'Kids' => ['Kids', 'kids', 'Everyday and occasion wear for children.'],
            'Accessories' => ['Accessories', 'accessories', 'Belts, scarves and small leather goods.'],
        ];

        $categories = [];

        foreach ($definitions as $index => [$departmentName, $slug, $description]) {
            $categories[$slug] = Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'department_id' => $departments[$departmentName]->id,
                    'name' => match (true) {
                        $departmentName === 'Kids' => 'Kids clothing',
                        $slug === 'menswear' => 'Suits & jackets',
                        default => Str::headline($slug),
                    },
                    'description' => $description,
                    'is_active' => true,
                    'position' => $index,
                    'is_featured' => in_array($slug, ['dresses', 'agbada', 'shoes', 'bags'], true),
                    'image_path' => $this->pickImage($slug)['url'] ?? null,
                    'meta_title' => Str::headline($slug).' — Nigerian made',
                    'meta_description' => $description,
                ],
            );
        }

        return $categories;
    }

    /* ------------------------------------------------------------------ *
     * Vendors
     * ------------------------------------------------------------------ */

    /** @return array<int,Vendor> */
    private function seedVendors(): array
    {
        $definitions = [
            ['Adire & Co.', 'Abeokuta', 'Ogun', 'Indigo adire and hand-dyed cotton, made beside the Ogun river.', true],
            ['Ìróko Atelier', 'Lagos', 'Lagos', 'Tailoring with a modern cut, from a small studio in Yaba.', true],
            ['Aso-Oke House', 'Iseyin', 'Oyo', 'Handwoven aso-oke from a family loom that has run for four generations.', true],
            ['Kano Leather Works', 'Kano', 'Kano', 'Vegetable-tanned leather goods, cut and stitched by hand.', true],
            ['Ndị Aba Shoemakers', 'Aba', 'Abia', 'Aba-made footwear — the workshop that outfits half of West Africa.', true],
            ['Lagos Streetwear Collective', 'Surulere', 'Lagos', 'Screen-printed streetwear with a Lagos state of mind.', true],
            ['Benin Brass Studio', 'Benin City', 'Edo', 'Cast brass and coral beadwork in the Benin court tradition.', false],
            ['Calabar Clay', 'Calabar', 'Cross River', 'Hand-thrown ceramics and natural-dye textiles.', false],
            ['Northern Loom', 'Kaduna', 'Kaduna', 'Groundnut-brown cotton and Hausa embroidery.', false],
            ['Ọ̀rẹ́ Studio', 'Enugu', 'Enugu', 'A collaborative studio for young Enugu designers.', false],
        ];

        $vendors = [];

        foreach ($definitions as $index => [$name, $city, $state, $description, $featured]) {
            $owner = User::firstOrCreate(
                ['email' => Str::slug($name, '.').'@hanbellshop.demo'],
                [
                    'name' => $name.' (owner)',
                    'password' => Hash::make('password'),
                    'phone' => '+23480'.str_pad((string) ($index + 10000000), 8, '0', STR_PAD_LEFT),
                    'email_verified_at' => now(),
                    'two_factor_method' => TwoFactorMethod::None,
                    'is_vendor' => true,
                ],
            );

            $owner->assignRole('vendor');

            $vendor = Vendor::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'owner_id' => $owner->id,
                    'name' => $name,
                    'legal_name' => $name.' Ltd',
                    'email' => Str::slug($name, '.').'@hanbellshop.demo',
                    'phone' => $owner->phone,
                    'website' => 'https://instagram.com/'.Str::slug($name, ''),
                    'description' => $description,
                    'story' => $description.' Every piece is made in small batches, so no two are exactly alike. We work with a handful of makers and pay them per piece, on the day.',
                    'city' => $city,
                    'state' => $state,
                    'country' => 'NG',
                    'status' => VendorStatus::Approved,
                    'approved_at' => now()->subDays(60 - $index * 3),
                    'applied_at' => now()->subDays(70 - $index * 3),
                    'is_featured' => $featured,
                ],
            );

            $vendors[] = $vendor;
        }

        return $vendors;
    }

    /* ------------------------------------------------------------------ *
     * Products
     * ------------------------------------------------------------------ */

    /**
     * @param  array<int,Vendor>  $vendors
     * @param  array<string,Category>  $categories
     */
    private function seedProducts(array $vendors, array $categories): void
    {
        // Name fragments per category, combined deterministically so the
        // catalogue reads like a real one without hand-writing 120 products.
        $templates = [
            'dresses' => ['Adire Wrap Dress', 'Ankara Midi Dress', 'Aso-Oke Occasion Gown', 'Linen Shirt Dress', 'Indigo Maxi Dress', 'Beaded Cocktail Dress', 'Adire Sundress', 'Lace Aso-Ebi Gown', 'Cotton Slip Dress', 'Embroidered Kaftan Dress'],
            'tops' => ['Ankara Peplum Top', 'Silk Wrap Blouse', 'Adire Boxy Shirt', 'Lace Detail Blouse', 'Indigo Tie-Front Top', 'Embroidered Cotton Top'],
            'skirts' => ['Aso-Oke Maxi Skirt', 'Ankara Wrap Skirt', 'Pencil Skirt', 'Adire Tiered Skirt', 'Pleated Midi Skirt'],
            'trousers' => ['Tailored Wool Trousers', 'Adire Wide-Leg Trousers', 'Cotton Chinos', 'Pleated Culottes'],
            'agbada' => ['Three-Piece Agbada', 'Embroidered Agbada Set', 'Ivory Agbada', 'Navy Agbada with Gold Thread', 'Minimal Agbada'],
            'kaftans' => ['Cotton Kaftan', 'Embroidered Kaftan', 'Senator Kaftan', 'Linen Kaftan', 'Beaded Kaftan'],
            'shirts' => ['Ankara Print Shirt', 'Plain Oxford Shirt', 'Adire Camp Collar Shirt', 'Embroidered Senator Shirt'],
            'menswear' => ['Two-Piece Suit', 'Ankara Dinner Jacket', 'Aso-Oke Waistcoat', 'Linen Blazer'],
            'shoes' => ['Hand-Stitched Loafers', 'Aba Leather Sandals', 'Block Heel Pumps', 'Ankara Print Sneakers', 'Leather Oxford Shoes', 'Beaded Slippers'],
            'bags' => ['Vegetable-Tanned Tote', 'Aso-Oke Clutch', 'Ankara Shoulder Bag', 'Leather Crossbody', 'Woven Market Basket'],
            'jewellery' => ['Coral Bead Necklace', 'Brass Cuff Bracelet', 'Beaded Statement Earrings', 'Cowrie Shell Pendant', 'Layered Brass Chain'],
            'headwraps' => ['Aso-Oke Gele', 'Ankara Headwrap', 'Adire Headwrap', 'Silk Turban'],
            'kids' => ['Kids Ankara Dress', 'Kids Adire Shirt', 'Kids Kaftan Set', 'Kids Cotton Shorts', 'Kids Occasion Gown'],
            'accessories' => ['Woven Leather Belt', 'Aso-Oke Scarf', 'Beaded Keyring', 'Leather Card Holder'],
        ];

        $categoryBySlug = $categories;
        $vendorCount = count($vendors);
        $productIndex = 0;

        foreach ($templates as $slug => $names) {
            $category = $categoryBySlug[$slug] ?? null;

            if (! $category) {
                continue;
            }

            foreach ($names as $nameIndex => $productName) {
                $vendor = $vendors[($productIndex + $nameIndex) % $vendorCount];

                // Prices in whole Naira, converted to kobo. A spread rather than
                // a flat figure, so filters and sorting have something to work on.
                $basePrice = match ($slug) {
                    'agbada', 'menswear', 'dresses' => random_int(45_000, 180_000),
                    'shoes', 'bags' => random_int(28_000, 95_000),
                    'jewellery', 'headwraps', 'accessories' => random_int(8_000, 45_000),
                    'kids' => random_int(12_000, 38_000),
                    default => random_int(18_000, 75_000),
                };

                $onSale = $productIndex % 3 === 0;
                $compareAt = $onSale ? (int) round($basePrice * random_int(115, 140) / 100) : null;

                $product = Product::updateOrCreate(
                    ['slug' => Str::slug($productName.' '.$vendor->name)],
                    [
                        'vendor_id' => $vendor->id,
                        'category_id' => $category->id,
                        'department_id' => $category->department_id,
                        'name' => $productName,
                        'sku' => strtoupper(Str::substr($slug, 0, 3)).'-'.str_pad((string) (++$productIndex), 4, '0', STR_PAD_LEFT),
                        'summary' => $this->summaryFor($productName, $vendor, $category),
                        'description' => $this->descriptionFor($productName, $vendor, $category),
                        'price_minor' => Money::toMinor($basePrice),
                        'compare_at_price_minor' => $compareAt === null ? null : Money::toMinor($compareAt),
                        'cost_minor' => Money::toMinor((int) round($basePrice * 0.55)),
                        'currency' => 'NGN',
                        'status' => ProductStatus::Published,
                        'published_at' => now()->subDays(random_int(1, 90)),
                        'approved_at' => now()->subDays(random_int(1, 90)),
                        'is_featured' => $productIndex % 7 === 0,
                        'is_new_arrival' => $productIndex % 5 === 0,
                        'is_trending' => $productIndex % 6 === 0,
                        'gender' => $this->genderFor($slug),
                        'material' => $this->materialFor($slug),
                        'care_instructions' => 'Dry clean or hand wash cold. Do not tumble dry.',
                        'country_of_origin' => 'NG',
                        'made_in_city' => $vendor->city,
                        'weight_grams' => random_int(250, 1800),
                        'meta_title' => $productName.' — '.$vendor->name.' on HanbellShop',
                        'meta_description' => Str::limit($this->summaryFor($productName, $vendor, $category), 155),
                        'llm_summary' => $this->llmSummaryFor($productName, $vendor, $category),
                        'llm_attributes' => [
                            'made_in' => $vendor->city.', Nigeria',
                            'made_by' => $vendor->name,
                            'category' => $category->name,
                            'material' => $this->materialFor($slug),
                            'care' => 'Dry clean or hand wash cold',
                        ],
                        'views_count' => random_int(20, 4000),
                        'sales_count' => random_int(0, 120),
                        'rating_average' => random_int(35, 50) / 10,
                        'rating_count' => random_int(0, 40),
                    ],
                );

                $this->seedVariants($product, $slug);
                $this->seedMedia($product, $slug, $nameIndex);
                $this->seedTags($product, $slug, $category);
            }
        }
    }

    private function seedVariants(Product $product, string $slug): void
    {
        $sizes = match ($slug) {
            'shoes' => ['38', '39', '40', '41', '42', '43', '44'],
            'bags', 'jewellery', 'headwraps', 'accessories' => ['One size'],
            'kids' => ['2-3Y', '4-5Y', '6-7Y', '8-9Y'],
            default => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
        };

        $colours = [
            ['Indigo', '#2c3e70'],
            ['Ochre', '#c68b2c'],
            ['Ivory', '#f4f1e8'],
            ['Black', '#171715'],
            ['Emerald', '#12694a'],
        ];

        // Two colourways per product, so the variant picker has something real
        // to switch between without exploding the row count.
        $chosen = collect($colours)->shuffle()->take(2)->values();

        $position = 0;

        foreach ($chosen as $colour) {
            foreach ($sizes as $size) {
                $variant = ProductVariant::updateOrCreate(
                    ['sku' => $product->sku.'-'.Str::upper(Str::substr($colour[0], 0, 2)).'-'.Str::slug($size)],
                    [
                        'product_id' => $product->id,
                        'name' => $size.' / '.$colour[0],
                        'size' => $size,
                        'color' => $colour[0],
                        'color_hex' => $colour[1],
                        'price_minor' => null,
                        'position' => $position++,
                        'is_active' => true,
                    ],
                );

                // Some variants are deliberately out of stock, so the sold-out
                // state on the product page is exercised by real data.
                $onHand = random_int(0, 14);

                Inventory::updateOrCreate(
                    ['variant_id' => $variant->id],
                    [
                        'product_id' => $product->id,
                        'quantity_on_hand' => $onHand,
                        'quantity_reserved' => 0,
                        'low_stock_threshold' => 3,
                        'allow_backorder' => false,
                    ],
                );
            }
        }
    }

    private function seedMedia(Product $product, string $slug, int $offset): void
    {
        $pool = $this->images['products'][$slug] ?? $this->images['products']['accessories'] ?? $this->images['editorial'] ?? [];

        if ($pool === []) {
            return;
        }

        // Two or three images per product, taken at an offset so neighbouring
        // products do not all show the same photograph.
        $count = min(3, count($pool));

        for ($i = 0; $i < $count; $i++) {
            $image = $pool[($offset + $i) % count($pool)];

            $product->media()->updateOrCreate(
                ['path' => $image['url']],
                [
                    'disk' => 'public',
                    'alt_text' => $product->name.' — '.$product->vendor?->name,
                    'position' => $i,
                    'is_primary' => $i === 0,
                    // Unsplash's licence requires attribution; it is rendered on
                    // the product page.
                    'credit_name' => $image['credit'] ?? null,
                    'credit_url' => $image['credit_url'] ?? null,
                ],
            );
        }

        Inventory::firstOrCreate(
            ['product_id' => $product->id, 'variant_id' => null],
            ['quantity_on_hand' => 0, 'quantity_reserved' => 0, 'low_stock_threshold' => 3],
        );
    }

    private function seedTags(Product $product, string $slug, Category $category): void
    {
        $names = array_filter([
            $category->name,
            match ($slug) {
                'agbada', 'kaftans' => 'Occasion',
                'dresses' => 'Wedding guest',
                'shoes', 'bags' => 'Everyday',
                default => null,
            },
            random_int(0, 1) ? 'Handmade' : null,
            random_int(0, 1) ? 'Limited run' : null,
        ]);

        foreach ($names as $name) {
            $tag = Tag::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'type' => in_array($name, ['Occasion', 'Wedding guest'], true) ? 'occasion' : 'general',
                ],
            );

            $product->tags()->syncWithoutDetaching([$tag->id]);
        }
    }

    /* ------------------------------------------------------------------ *
     * Advertising
     * ------------------------------------------------------------------ */

    private function seedAdPlacements(): void
    {
        foreach (AdPlacementKey::cases() as $key) {
            AdPlacement::updateOrCreate(
                ['key' => $key->value],
                [
                    'label' => $key->label(),
                    'description' => $key->description(),
                    'recommended_size' => $key->recommendedSize(),
                    'aspect_class' => $key->aspectClass(),
                    'max_creatives' => $key->maxCreatives(),
                    'is_active' => true,
                ],
            );
        }
    }

    /** @param array<int,Vendor> $vendors */
    private function seedAdvertising(array $vendors): void
    {
        $editorial = $this->images['editorial'] ?? [];
        $hero = $this->images['hero'] ?? [];

        $definitions = [
            ['Aso-Oke House — Wedding Season', AdPlacementKey::HomeHero, 250_000, 60, true],
            ['Aba Shoemakers — New Season', AdPlacementKey::HomeMidBanner, 180_000, 45, false],
            ['Adire & Co. — Indigo Story', AdPlacementKey::HomeStrip, 90_000, 30, false],
            ['Kano Leather — Craft', AdPlacementKey::HomeSidebar, 120_000, 40, false],
            ['Lagos Streetwear — Drop 04', AdPlacementKey::ShopInline, 150_000, 35, false],
            ['Ìróko Atelier — Bespoke', AdPlacementKey::CategoryTopBanner, 140_000, 28, false],
            ['Benin Brass — Heritage', AdPlacementKey::FooterBanner, 70_000, 50, false],
        ];

        foreach ($definitions as $index => [$name, $placement, $budget, $bid, $exclusive]) {
            $vendor = $vendors[$index % count($vendors)];
            $image = $editorial[$index % max(1, count($editorial))] ?? ($hero[0] ?? null);

            $advertiser = Advertiser::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'vendor_id' => $vendor->id,
                    'name' => $name,
                    'contact_name' => $vendor->name.' Marketing',
                    'contact_email' => 'ads@'.Str::slug($vendor->name, '').'.demo',
                    'is_active' => true,
                ],
            );

            $campaign = AdCampaign::updateOrCreate(
                ['name' => $name],
                [
                    'advertiser_id' => $advertiser->id,
                    'vendor_id' => $vendor->id,
                    'description' => 'Demo campaign for '.$vendor->name.'.',
                    'status' => AdCampaignStatus::Active,
                    'pricing_model' => AdPricingModel::Cpm,
                    'budget_minor' => Money::toMinor($budget),
                    'bid_minor' => Money::toMinor($bid),
                    'spend_minor' => Money::toMinor((int) round($budget * random_int(10, 55) / 100)),
                    'starts_at' => now()->subDays(10),
                    'ends_at' => now()->addDays(40),
                    'audience' => 'everyone',
                    'device' => 'all',
                    'max_impressions_per_session' => 3,
                    'priority' => $index === 0 ? 2 : 0,
                    'is_exclusive' => $exclusive,
                    'impressions_count' => random_int(500, 40_000),
                    'clicks_count' => random_int(10, 1_200),
                ],
            );

            $placementModel = AdPlacement::where('key', $placement->value)->first();

            if ($placementModel) {
                $campaign->placements()->syncWithoutDetaching([$placementModel->id]);
            }

            AdCreative::updateOrCreate(
                ['ad_campaign_id' => $campaign->id, 'name' => $name],
                [
                    'headline' => $vendor->name,
                    'subheadline' => 'Made in '.$vendor->city,
                    'cta_label' => 'Shop the collection',
                    'image_url' => $image['url'] ?? null,
                    'destination_url' => '/shop',
                    'destination_type' => 'internal',
                    'background_color' => '#0b0b0a',
                    'text_color' => '#ffffff',
                    'is_active' => true,
                    'weight' => 1,
                    'impressions_count' => random_int(100, 20_000),
                    'clicks_count' => random_int(5, 600),
                ],
            );
        }
    }

    /* ------------------------------------------------------------------ *
     * Copy helpers
     * ------------------------------------------------------------------ */

    private function pickImage(string $slug): ?array
    {
        $pool = $this->images['products'][$slug] ?? $this->images['editorial'] ?? [];

        return $pool[0] ?? null;
    }

    private function summaryFor(string $name, Vendor $vendor, Category $category): string
    {
        return sprintf(
            '%s by %s, made in %s. %s',
            $name,
            $vendor->name,
            $vendor->city,
            $category->description,
        );
    }

    private function descriptionFor(string $name, Vendor $vendor, Category $category): string
    {
        return implode("\n\n", [
            sprintf('%s is cut and finished by hand at %s in %s, %s.', $name, $vendor->name, $vendor->city, $vendor->state),
            $vendor->description,
            'Because each piece is made in small batches, slight variations in colour and finish are normal and part of what makes it yours.',
            'Ships within 1–3 working days. Made-to-order pieces take a little longer, and we will confirm the timeline by email.',
        ]);
    }

    private function llmSummaryFor(string $name, Vendor $vendor, Category $category): string
    {
        return sprintf(
            '%s is a %s from %s, a Nigerian brand based in %s, %s. It is made in Nigeria and sold on HanbellShop. Category: %s.',
            $name,
            Str::lower($category->name),
            $vendor->name,
            $vendor->city,
            $vendor->state,
            $category->name,
        );
    }

    private function genderFor(string $slug): string
    {
        return match ($slug) {
            'agbada', 'kaftans', 'shirts', 'menswear' => 'men',
            'dresses', 'tops', 'skirts', 'trousers' => 'women',
            'kids' => 'kids',
            default => 'unisex',
        };
    }

    private function materialFor(string $slug): string
    {
        return match ($slug) {
            'agbada', 'kaftans', 'shirts', 'menswear' => 'Cotton and aso-oke',
            'dresses', 'tops', 'skirts', 'trousers' => 'Ankara cotton',
            'shoes', 'bags', 'accessories' => 'Vegetable-tanned leather',
            'jewellery' => 'Brass and coral bead',
            'headwraps' => 'Aso-oke',
            default => 'Cotton',
        };
    }
}
