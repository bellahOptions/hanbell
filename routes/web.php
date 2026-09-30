<?php

use App\Http\Controllers\Advertising\AdTrackingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\DocumentController;
use App\Http\Controllers\Storefront\LlmController;
use App\Http\Controllers\Storefront\RobotsController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use App\Support\Locale;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
|
| Public URLs carry signed, opaque tokens (App\Support\Tokens\UrlToken) rather
| than ids or uuids. The token occupies the identifying route segment and the
| human-readable slug rides along afterwards purely for readability and SEO —
| the slug is never used for lookup, so it can change without breaking a link.
|
| Route-model binding resolves the token and returns null (a 404) for anything
| that fails verification, so a tampered or expired link is indistinguishable
| from a missing page.
|
*/

/* -------------------------------------------------------------------------
 | Locale switching
 |------------------------------------------------------------------------ */
Route::get('locale/{locale}', [LocaleController::class, 'update'])
    ->name('locale.update')
    ->whereIn('locale', Locale::codes());

/* -------------------------------------------------------------------------
 | SEO / LLM discovery endpoints
 |------------------------------------------------------------------------ */
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('sitemap-{section}.xml', [SitemapController::class, 'section'])
    ->name('sitemap.section')
    ->where('section', 'products|categories|vendors|pages');

Route::get('robots.txt', [RobotsController::class, 'index'])->name('robots');

// The emerging convention for telling language models what a site contains.
Route::get('llms.txt', [LlmController::class, 'index'])->name('llms');
Route::get('llms-full.txt', [LlmController::class, 'full'])->name('llms.full');

/* -------------------------------------------------------------------------
 | Advertising
 |------------------------------------------------------------------------ */
Route::post('ads/impression', [AdTrackingController::class, 'impression'])
    ->name('ads.impression')
    ->middleware('throttle:120,1');

Route::get('ads/click/{creative}', [AdTrackingController::class, 'click'])
    ->name('ads.click')
    ->middleware('throttle:60,1');

/* -------------------------------------------------------------------------
 | Payment webhooks (server-to-server; signature-verified, CSRF-exempt)
 |------------------------------------------------------------------------ */
Route::post('webhooks/{provider}', PaymentWebhookController::class)
    ->name('webhooks.payment')
    ->whereIn('provider', ['paystack', 'flutterwave', 'stripe', 'paypal'])
    ->middleware('throttle:120,1');

/* -------------------------------------------------------------------------
 | Payment callback (browser return from a hosted checkout)
 |------------------------------------------------------------------------ */
Route::get('checkout/callback/{provider}', [CheckoutController::class, 'callback'])
    ->name('checkout.callback')
    ->whereIn('provider', ['paystack', 'flutterwave', 'stripe', 'paypal']);

Route::get('checkout/cancelled/{provider}', [CheckoutController::class, 'cancelled'])
    ->name('checkout.cancel')
    ->whereIn('provider', ['paystack', 'flutterwave', 'stripe', 'paypal']);

/* -------------------------------------------------------------------------
 | Authentication
 |------------------------------------------------------------------------ */
Route::middleware('auth')->group(function (): void {
    Route::get('verify-email', App\Livewire\Auth\VerifyEmail::class)->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', App\Http\Controllers\Auth\VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::get('two-factor-challenge', App\Livewire\Auth\TwoFactorChallenge::class)
        ->name('two-factor.challenge');

    Route::post('logout', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

Route::middleware('guest')->group(function (): void {
    Route::get('login', App\Livewire\Auth\Login::class)->name('login');
    Route::get('register', App\Livewire\Auth\Register::class)->name('register');
    Route::get('forgot-password', App\Livewire\Auth\ForgotPassword::class)->name('password.request');
    Route::get('reset-password/{token}', App\Livewire\Auth\ResetPassword::class)->name('password.reset');
});

/* -------------------------------------------------------------------------
 | Admin
 |------------------------------------------------------------------------ */
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(base_path('routes/admin.php'));

/* -------------------------------------------------------------------------
 | Vendor panel
 |------------------------------------------------------------------------ */
Route::middleware(['auth', 'role:vendor|admin'])
    ->prefix('vendor')
    ->name('vendor.')
    ->group(base_path('routes/vendor.php'));

/* -------------------------------------------------------------------------
 | PDF documents (invoices, receipts, credit notes, packing slips, statements)
 |------------------------------------------------------------------------
 |
 | Authorisation lives in the controller, which resolves the order from its
 | signed token and then checks ownership. Rate-limited because rendering a PDF
 | is comparatively expensive and a loop of them would be a cheap denial of
 | service.
 |
*/
Route::middleware('throttle:60,1')->prefix('documents')->name('documents.')->group(function (): void {
    Route::get('orders/{order}/invoice', [DocumentController::class, 'invoice'])->name('invoice');
    Route::get('orders/{order}/receipt', [DocumentController::class, 'receipt'])->name('receipt');
    Route::get('orders/{order}/credit-note', [DocumentController::class, 'creditNote'])->name('credit-note');
    Route::get('orders/{order}/packing-slip', [DocumentController::class, 'packingSlip'])->name('packing-slip');
    Route::get('orders/{order}/vendor/{vendorOrder}/statement', [DocumentController::class, 'vendorStatement'])
        ->whereNumber('vendorOrder')
        ->name('vendor-statement');

    // Public: published material a customer reads before buying.
    Route::get('terms-of-service', [DocumentController::class, 'terms'])->name('terms');
});

/* -------------------------------------------------------------------------
 | Storefront (catch-all — keep last so it cannot shadow the routes above)
 |------------------------------------------------------------------------ */
Route::name('storefront.')->group(base_path('routes/storefront.php'));
