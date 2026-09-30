<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront routes
|--------------------------------------------------------------------------
|
| Tokens, not ids. The `{product}` / `{category}` / `{vendor}` parameters are
| resolved by App\Models\Concerns\HasUrlToken::resolveRouteBinding(), which
| verifies the signature and the record's token version. The trailing `{slug}`
| is decorative — it exists so a URL reads well and ranks, never for lookup.
|
*/

Route::get('/', App\Livewire\Storefront\Home::class)->name('home');

/* -------------------------------------------------------------------------
 | Catalogue
 |------------------------------------------------------------------------ */
Route::get('shop', App\Livewire\Storefront\Shop::class)->name('shop');

Route::get('search', App\Livewire\Storefront\Shop::class)->name('search');

Route::get('product/{product}/{slug?}', App\Livewire\Storefront\ProductShow::class)
    ->name('products.show');

/*
 * Listing scopes.
 *
 * These render the same `Shop` component, so route-model binding does not apply
 * (an inline Livewire component is not a controller and never sees a bound
 * model). The token is therefore verified explicitly inside the component's
 * mount() via the model's own resolveRouteBinding() — which is the same code
 * path the router would have used, so the security properties are identical.
 *
 * The trailing {slug} is decorative and only exists to make the URL readable.
 */
Route::get('category/{category}/{slug?}', App\Livewire\Storefront\Shop::class)
    ->name('categories.show');

Route::get('department/{department}/{slug?}', App\Livewire\Storefront\Shop::class)
    ->name('departments.show');

Route::get('brand/{brand}/{slug?}', App\Livewire\Storefront\Shop::class)
    ->name('brands.show');

Route::get('tag/{tag}/{slug?}', App\Livewire\Storefront\Shop::class)
    ->name('tags.show');

/* -------------------------------------------------------------------------
 | Vendors (the Nigerian brands and creators selling on HanbellShop)
 |------------------------------------------------------------------------ */
Route::get('brands', App\Livewire\Storefront\VendorDirectory::class)->name('vendors.index');
Route::get('brands/{vendor}/{slug?}', App\Livewire\Storefront\VendorShow::class)->name('vendors.show');
Route::get('sell-with-us', App\Livewire\Storefront\VendorApply::class)->name('vendors.apply');

/* -------------------------------------------------------------------------
 | CMS pages (policies, about, help)
 |------------------------------------------------------------------------ */
Route::get('pages/{slug}', App\Livewire\Storefront\PageShow::class)->name('pages.show');

/* -------------------------------------------------------------------------
 | Cart & wishlist — administrators are bounced out by the `customer` alias
 |------------------------------------------------------------------------ */
Route::middleware('customer')->group(function (): void {
    Route::get('cart', App\Livewire\Storefront\Cart::class)->name('cart');
    Route::get('wishlist', App\Livewire\Storefront\Wishlist::class)->name('wishlist');
    Route::get('checkout', App\Livewire\Storefront\Checkout::class)->name('checkout');
});

/* -------------------------------------------------------------------------
 | Orders — guest orders are reachable via the opaque token in the URL
 |------------------------------------------------------------------------ */
Route::get('orders/{order}', App\Livewire\Storefront\OrderShow::class)->name('orders.show');
Route::get('order-tracking', App\Livewire\Storefront\OrderTracking::class)->name('orders.track');

/* -------------------------------------------------------------------------
 | Customer account
 |------------------------------------------------------------------------ */
Route::middleware(['auth', 'customer'])->prefix('account')->name('account.')->group(function (): void {
    Route::get('/', App\Livewire\Account\Dashboard::class)->name('dashboard');
    Route::get('orders', App\Livewire\Account\Orders::class)->name('orders');
    Route::get('addresses', App\Livewire\Account\Addresses::class)->name('addresses');
    Route::get('security', App\Livewire\Account\Security::class)->name('security');
    Route::get('wishlist', App\Livewire\Account\Wishlist::class)->name('wishlist');
});

/* -------------------------------------------------------------------------
 | Success / confirmation landing pages
 |------------------------------------------------------------------------ */
Route::get('order-placed/{order}', App\Livewire\Storefront\OrderPlaced::class)
    ->name('orders.placed');

/* -------------------------------------------------------------------------
 | Misc
 |------------------------------------------------------------------------ */
Route::get('contact', App\Livewire\Storefront\Contact::class)->name('contact');
