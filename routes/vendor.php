<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vendor panel routes
|--------------------------------------------------------------------------
|
| Behind auth + role:vendor|admin. Vendors manage their own catalogue, stock
| and fulfilment here; they cannot see or touch another vendor's data.
|
*/

Route::get('/', App\Livewire\Vendor\Dashboard::class)->name('dashboard');

Route::get('products', App\Livewire\Vendor\ProductIndex::class)->name('products.index');
Route::get('products/create', App\Livewire\Vendor\ProductForm::class)->name('products.create');
Route::get('products/{product}/edit', App\Livewire\Vendor\ProductForm::class)->name('products.edit');

Route::get('inventory', App\Livewire\Vendor\InventoryIndex::class)->name('inventory.index');
Route::get('orders', App\Livewire\Vendor\OrderIndex::class)->name('orders.index');
Route::get('profile', App\Livewire\Vendor\Profile::class)->name('profile');
