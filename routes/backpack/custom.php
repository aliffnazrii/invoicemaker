<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProductCrudController;
use App\Http\Controllers\Admin\ContactCrudController;
use App\Http\Controllers\Admin\AdminController;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::get('dashboard', [AdminController::class, 'dashboard']);
    Route::get('/', [AdminController::class, 'redirect']);

    Route::crud('user', 'UserCrudController');
    Route::crud('invoice', 'InvoiceCrudController');
    Route::post('/download-invoice', 'InvoiceCrudController@invoice');
    Route::get('invoice/{id}/redownload-invoice', 'InvoiceCrudController@redownload')->name('invoice.redownload');
    Route::post('invoice/{id}/mark-paid', 'InvoiceCrudController@markPaid')->name('invoice.mark_paid');
    Route::crud('setting', 'SettingCrudController');
    Route::crud('contact', 'ContactCrudController');
    Route::crud('product', 'ProductCrudController');

    Route::get('api/products/search', [ProductCrudController::class, 'fetch'])->name('product.search');
    Route::get('api/contacts/search', [ContactCrudController::class, 'fetch'])->name('contact.search');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
