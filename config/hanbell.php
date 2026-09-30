<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Store identity
    |--------------------------------------------------------------------------
    |
    | NOTE ON THE OPERATING COMPANY
    |
    | HanbellShop is a trading name of Bellah Options. Financial and legal
    | documents (invoices, receipts, credit notes, the terms of service) must
    | name the operating entity, not just the brand, or they are of limited use
    | to a customer's accountant or to a regulator.
    |
    | The registration, tax and address fields below are deliberately EMPTY.
    | A Nigerian invoice is expected to carry the issuer's RC number and TIN,
    | and inventing them would produce documents that look authoritative and are
    | wrong. Fill them in through Admin -> Settings, or here, before issuing a
    | real invoice. The PDFs omit any line whose value is blank rather than
    | printing a placeholder.
    |
    */

    'name' => env('HANBELL_STORE_NAME', 'HanbellShop'),

    // The legal entity behind the brand.
    'operator' => [
        'name' => env('HANBELL_OPERATOR_NAME', 'Bellah Options'),
        'trading_name' => env('HANBELL_STORE_NAME', 'HanbellShop'),
        // e.g. "RC 1234567" — leave blank until confirmed.
        'registration_number' => env('HANBELL_OPERATOR_RC', ''),
        // Tax Identification Number.
        'tin' => env('HANBELL_OPERATOR_TIN', ''),
        'vat_number' => env('HANBELL_OPERATOR_VAT', ''),
        'address_line' => env('HANBELL_OPERATOR_ADDRESS', ''),
        'city' => env('HANBELL_OPERATOR_CITY', ''),
        'state' => env('HANBELL_OPERATOR_STATE', ''),
        'country' => env('HANBELL_OPERATOR_COUNTRY', 'NG'),
        'email' => env('HANBELL_SUPPORT_EMAIL', 'hello@hanbellshop.test'),
        'phone' => env('HANBELL_SUPPORT_PHONE', '+234 800 000 0000'),
        'website' => env('HANBELL_OPERATOR_WEBSITE', ''),
        // Shown on the terms document as the governing jurisdiction.
        'jurisdiction' => env('HANBELL_OPERATOR_JURISDICTION', 'the Federal Republic of Nigeria'),
        // Days allowed to respond to a formal complaint, per the terms.
        'complaint_response_days' => (int) env('HANBELL_COMPLAINT_DAYS', 30),
    ],

    'tagline' => env('HANBELL_TAGLINE', 'Quality Nigerian fashion, at Hanbell prices.'),

    'description' => env(
        'HANBELL_DESCRIPTION',
        'HanbellShop is a multi-vendor marketplace for fashion made by indigenous Nigerian brands and creators — quality pieces at fair, affordable prices.'
    ),

    'support_email' => env('HANBELL_SUPPORT_EMAIL', 'hello@hanbellshop.test'),

    'support_phone' => env('HANBELL_SUPPORT_PHONE', '+234 800 000 0000'),

    'whatsapp' => env('HANBELL_WHATSAPP', '+2348000000000'),

    'address' => env('HANBELL_ADDRESS', 'Lagos, Nigeria'),

    /*
    |--------------------------------------------------------------------------
    | PDF documents
    |--------------------------------------------------------------------------
    |
    | Invoices, receipts, credit notes, packing slips and the terms of service
    | are rendered by dompdf/dompdf (the engine directly — the Laravel wrapper
    | caps at Laravel 11).
    |
    */

    'pdf' => [
        // Default page setup for financial documents. A5 is used for receipts.
        'paper' => env('HANBELL_PDF_PAPER', 'a4'),
        'orientation' => 'portrait',

        // Body font. DejaVu Sans is bundled with dompdf and — verified by
        // rendering and reading the output back — covers the Naira sign (U+20A6)
        // and Nigerian combining diacritics such as Ọ̀rẹ́, so no text needs to be
        // ASCII-folded to survive PDF generation.
        'font' => 'DejaVu Sans',
        'base_font_size' => 9.5,

        // Brand palette, mirrored from resources/css/app.css.
        'colours' => [
            'brand' => '#178508',
            'accent' => '#FFF200',
            'ink' => '#0b0b0a',
            'muted' => '#74746c',
            'rule' => '#e2e2df',
            'surface' => '#f7f7f6',
        ],

        // Images are embedded from disk/chroot, never fetched. Remote loading is
        // off, so a document can never hang waiting on an external host.
        'remote_images' => false,

        // Where dompdf caches its font metrics and any generated font subsets.
        'font_dir' => storage_path('app/dompdf/fonts'),

        // Documents are stored here after generation so a customer can be given
        // a stable link and so an issued invoice is reproducible.
        'disk' => env('HANBELL_PDF_DISK', 'local'),
        'path' => 'documents',

        // A signed, expiring link is what customers receive.
        'link_ttl_days' => (int) env('HANBELL_PDF_LINK_TTL_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Commerce
    |--------------------------------------------------------------------------
    |
    | All monetary values are integer minor units (kobo). Never floats.
    |
    */

    'currency' => [
        'default' => env('HANBELL_CURRENCY', 'NGN'),
        'supported' => ['NGN', 'USD', 'GBP', 'EUR'],
    ],

    'commission' => [
        // Fallback when neither the vendor nor the category overrides it.
        'default_percent' => (float) env('HANBELL_COMMISSION_DEFAULT_PERCENT', 12),
    ],

    'shipping' => [
        'flat_minor' => (int) env('HANBELL_FLAT_SHIPPING_MINOR', 200000),          // ₦2,000
        'free_threshold_minor' => (int) env('HANBELL_FREE_SHIPPING_THRESHOLD_MINOR', 5000000), // ₦50,000
        // States we ship to; empty means nationwide.
        'restricted_states' => [],
    ],

    'tax' => [
        // Nigerian VAT. Set to 0 to disable tax lines entirely.
        'vat_percent' => (float) env('HANBELL_VAT_PERCENT', 7.5),
        'prices_include_tax' => (bool) env('HANBELL_PRICES_INCLUDE_TAX', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Localisation
    |--------------------------------------------------------------------------
    */

    'locales' => [
        // code => [native name, English name, direction, flag emoji]
        'en' => ['English', 'English', 'ltr', '🇬🇧'],
        'ha' => ['Hausa', 'Hausa', 'ltr', '🇳🇬'],
        'ig' => ['Igbo', 'Igbo', 'ltr', '🇳🇬'],
        'yo' => ['Yorùbá', 'Yoruba', 'ltr', '🇳🇬'],
        'fr' => ['Français', 'French', 'ltr', '🇫🇷'],
        'ar' => ['العربية', 'Arabic', 'rtl', '🇸🇦'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Advertising
    |--------------------------------------------------------------------------
    */

    'ads' => [
        // Only these hosts may be used as an external ad destination. Anything
        // else is refused at save time and at click time, which closes the
        // open-redirect hole an unvalidated destination would otherwise open.
        'allowed_redirect_hosts' => array_filter(explode(',', (string) env(
            'HANBELL_AD_ALLOWED_HOSTS',
            'hanbellshop.test,www.hanbellshop.test,instagram.com,facebook.com,x.com,twitter.com,tiktok.com,youtube.com'
        ))),

        // TTL for the signed click-through token, in minutes.
        'click_token_ttl' => 120,

        /*
         * Ranking weights for the ad server.
         *
         * These are the dials an operator actually turns, so they live in
         * config rather than as constants buried in AdRanker. Leaving `ranking`
         * empty uses AdRanker::defaultWeights().
         *
         * Example: to prioritise even delivery over revenue, raise
         * `pacing.max` and lower `quality.weight`.
         */
        'ranking' => [
            // 'pacing.max' => 6.0,
            // 'quality.weight' => 0.25,
        ],

        'aggregate' => [
            'lookback_days' => (int) env('HANBELL_AD_AGGREGATE_DAYS', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO & LLM
    |--------------------------------------------------------------------------
    */

    'seo' => [
        'title_suffix' => env('HANBELL_SEO_TITLE_SUFFIX', 'HanbellShop'),
        'default_description' => env(
            'HANBELL_SEO_DESCRIPTION',
            'Shop quality fashion from indigenous Nigerian brands and creators. Dresses, agbada, ankara, aso-oke, shoes and accessories at fair, affordable prices.'
        ),
        'twitter_handle' => env('HANBELL_TWITTER_HANDLE', '@hanbellshop'),
        'sitemap_cache_minutes' => (int) env('HANBELL_SITEMAP_CACHE_MINUTES', 60),
        'llms_txt_enabled' => (bool) env('HANBELL_LLMS_TXT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | URL tokenisation
    |--------------------------------------------------------------------------
    |
    | Public URLs carry a signed, opaque token instead of a raw id or uuid.
    | Tokens are HMAC-signed and embed the model's `token_version`, so
    | bumping that column invalidates every previously issued link for a
    | record without changing its uuid.
    |
    */

    'tokens' => [
        'ttl_days' => (int) env('HANBELL_TOKEN_TTL_DAYS', 30),
        'rotate_on_expiry' => (bool) env('HANBELL_TOKEN_ROTATE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    */

    'media' => [
        // 'local' stores uploads on the public disk; 'cloudinary' uploads to
        // Cloudinary's REST API when credentials are configured.
        'disk' => env('HANBELL_MEDIA_DISK', 'public'),
        'max_upload_kb' => (int) env('HANBELL_MAX_UPLOAD_KB', 4096),
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo / seed data
    |--------------------------------------------------------------------------
    */

    'demo' => [
        'enabled' => (bool) env('HANBELL_DEMO_DATA', true),
        'show_disclosure' => (bool) env('HANBELL_DEMO_DISCLOSURE', true),
    ],
];
