<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment gateways
    |--------------------------------------------------------------------------
    |
    | Each rail below is a real integration. With no credentials configured the
    | gateway reports itself as unavailable and the checkout page hides it —
    | it never fakes a successful payment.
    |
    */

    'default' => env('HANBELL_PAYMENT_DEFAULT', 'paystack'),

    'paystack' => [
        'label' => 'Paystack',
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        'currencies' => ['NGN'],
    ],

    'flutterwave' => [
        'label' => 'Flutterwave',
        'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        'webhook_secret' => env('FLUTTERWAVE_WEBHOOK_SECRET'),
        'base_url' => env('FLUTTERWAVE_BASE_URL', 'https://api.flutterwave.com/v3'),
        'currencies' => ['NGN', 'USD', 'GBP', 'EUR', 'GHS', 'KES', 'ZAR'],
    ],

    'stripe' => [
        'label' => 'Stripe',
        'public_key' => env('STRIPE_KEY'),
        'secret_key' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'base_url' => env('STRIPE_BASE_URL', 'https://api.stripe.com/v1'),
        'currencies' => ['USD', 'GBP', 'EUR', 'CAD', 'AUD'],
    ],

    'paypal' => [
        'label' => 'PayPal',
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
        'base_url' => env('PAYPAL_BASE_URL', 'https://api-m.sandbox.paypal.com'),
        'currencies' => ['USD', 'GBP', 'EUR', 'CAD', 'AUD'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Offline rails
    |--------------------------------------------------------------------------
    */

    'bank_transfer' => [
        'label' => 'Bank transfer',
        'bank_name' => env('HANBELL_BANK_NAME', 'Hanbell Demo Bank'),
        'account_name' => env('HANBELL_BANK_ACCOUNT_NAME', 'HanbellShop Ltd'),
        'account_number' => env('HANBELL_BANK_ACCOUNT_NUMBER', '0123456789'),
    ],

    'cash_on_delivery' => [
        'label' => 'Pay on delivery',
        'enabled' => (bool) env('HANBELL_COD_ENABLED', true),
        // Delivery-POD is only offered in these states.
        'states' => ['Lagos', 'Abuja', 'FCT', 'Rivers'],
        'fee_minor' => (int) env('HANBELL_COD_FEE_MINOR', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Behaviour
    |--------------------------------------------------------------------------
    */

    // A payment must be re-verified against the provider's API before an order
    // is marked paid. Never trust the browser callback or a webhook payload.
    'verify_on_callback' => true,

    'webhook_tolerance_seconds' => 300,

    // Currencies the storefront may quote. Conversion happens at display time.
    'display_currencies' => ['NGN', 'USD', 'GBP', 'EUR'],
];
