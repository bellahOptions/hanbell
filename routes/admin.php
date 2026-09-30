<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
|
| Everything here sits behind auth + role:admin (applied in web.php). The admin
| panel is its own UI: a shadcn-inspired, dense, data-first interface with a
| persistent sidebar, built with the same Tailwind design tokens as the
| storefront but a neutral, low-chrome treatment.
|
*/

Route::get('/', App\Livewire\Admin\Dashboard::class)->name('dashboard');

/* -------------------------------------------------------------------------
 | Catalogue
 |------------------------------------------------------------------------ */
Route::get('products', App\Livewire\Admin\Products\ProductIndex::class)->name('products.index');
Route::get('products/create', App\Livewire\Admin\Products\ProductForm::class)->name('products.create');
Route::get('products/{product}/edit', App\Livewire\Admin\Products\ProductForm::class)->name('products.edit');

Route::get('categories', App\Livewire\Admin\Catalogue\CategoryIndex::class)->name('categories.index');
Route::get('departments', App\Livewire\Admin\Catalogue\DepartmentIndex::class)->name('departments.index');
Route::get('brands', App\Livewire\Admin\Catalogue\BrandIndex::class)->name('brands.index');
Route::get('attributes', App\Livewire\Admin\Catalogue\AttributeIndex::class)->name('attributes.index');
Route::get('tags', App\Livewire\Admin\Catalogue\TagIndex::class)->name('tags.index');
Route::get('inventory', App\Livewire\Admin\Catalogue\InventoryIndex::class)->name('inventory.index');

/* -------------------------------------------------------------------------
 | Vendors
 |------------------------------------------------------------------------ */
Route::get('vendors', App\Livewire\Admin\Vendors\VendorIndex::class)->name('vendors.index');
Route::get('vendors/{vendor}', App\Livewire\Admin\Vendors\VendorShow::class)->name('vendors.show');

/* -------------------------------------------------------------------------
 | Orders & payments
 |------------------------------------------------------------------------ */
Route::get('orders', App\Livewire\Admin\Orders\OrderIndex::class)->name('orders.index');
Route::get('orders/{order}', App\Livewire\Admin\Orders\OrderShow::class)->name('orders.show');
Route::get('payments', App\Livewire\Admin\Orders\PaymentIndex::class)->name('payments.index');

/* -------------------------------------------------------------------------
 | Customers
 |------------------------------------------------------------------------ */
Route::get('customers', App\Livewire\Admin\Customers\CustomerIndex::class)->name('customers.index');
Route::get('customers/{user}', App\Livewire\Admin\Customers\CustomerShow::class)->name('customers.show');

/* -------------------------------------------------------------------------
 | Advertising — the algorithm's control surface
 |------------------------------------------------------------------------ */
Route::get('advertising', App\Livewire\Admin\Ads\CampaignIndex::class)->name('ads.index');
Route::get('advertising/placements', App\Livewire\Admin\Ads\PlacementIndex::class)->name('ads.placements');
Route::get('advertising/advertisers', App\Livewire\Admin\Ads\AdvertiserIndex::class)->name('ads.advertisers');
Route::get('advertising/reports', App\Livewire\Admin\Ads\Reports::class)->name('ads.reports');

/* -------------------------------------------------------------------------
 | Content
 |------------------------------------------------------------------------ */
Route::get('pages', App\Livewire\Admin\Content\PageIndex::class)->name('pages.index');
Route::get('pages/create', App\Livewire\Admin\Content\PageForm::class)->name('pages.create');
Route::get('pages/{page}/edit', App\Livewire\Admin\Content\PageForm::class)->name('pages.edit');
Route::get('reviews', App\Livewire\Admin\Content\ReviewIndex::class)->name('reviews.index');
Route::get('newsletter', App\Livewire\Admin\Content\NewsletterIndex::class)->name('newsletter.index');

/* -------------------------------------------------------------------------
 | SEO & LLM
 |------------------------------------------------------------------------ */
Route::get('seo', App\Livewire\Admin\Seo\SeoIndex::class)->name('seo.index');
Route::get('seo/redirects', App\Livewire\Admin\Seo\RedirectIndex::class)->name('seo.redirects');

/* -------------------------------------------------------------------------
 | Settings & audit
 |------------------------------------------------------------------------ */
Route::get('settings', App\Livewire\Admin\Settings\SettingsIndex::class)->name('settings.index');
Route::get('audit', App\Livewire\Admin\Settings\AuditIndex::class)->name('audit.index');
