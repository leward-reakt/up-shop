<?php

use App\Http\Controllers\Account\AddressController as AccountAddressController;
use App\Http\Controllers\Account\DashboardController as AccountDashboardController;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContentPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PayMongoPaymentController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ShopController;
use App\Models\Page;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)
    ->name('home');

Route::get('/shop', [ShopController::class, 'index'])
    ->name('shop.index');

Route::get(
    '/products/{product:slug}',
    [ShopController::class, 'show'],
)->name('products.show');

Route::get(
    '/sitemap.xml',
    [SeoController::class, 'sitemap'],
)->name('seo.sitemap');

Route::get(
    '/robots.txt',
    [SeoController::class, 'robots'],
)->name('seo.robots');

Route::get('/cart', [CartController::class, 'index'])
    ->name('cart.index');

Route::post('/cart/items', [CartController::class, 'store'])
    ->name('cart.items.store');

Route::patch(
    '/cart/items/{product}',
    [CartController::class, 'update'],
)->name('cart.items.update');

Route::delete(
    '/cart/items',
    [CartController::class, 'destroyMany'],
)->name('cart.items.destroy-many');

Route::delete(
    '/cart/items/{productId}',
    [CartController::class, 'destroy'],
)->name('cart.items.destroy');

Route::post(
    '/cart/discount',
    [CartController::class, 'applyDiscount'],
)->name('cart.discount.store');

Route::delete(
    '/cart/discount',
    [CartController::class, 'removeDiscount'],
)->name('cart.discount.destroy');

Route::get(
    '/checkout',
    [CheckoutController::class, 'index'],
)->name('checkout.index');

Route::post(
    '/checkout',
    [CheckoutController::class, 'store'],
)
    ->middleware('throttle:checkout')
    ->name('checkout.store');

Route::get(
    '/checkout/success',
    [CheckoutController::class, 'success'],
)->name('checkout.success');

Route::get(
    '/checkout/payment/{order:order_number}',
    [PayMongoPaymentController::class, 'show'],
)
    ->middleware('signed')
    ->name('checkout.payment.show');

Route::get(
    '/checkout/payment/{order:order_number}/success',
    [PayMongoPaymentController::class, 'success'],
)
    ->middleware('signed')
    ->name('checkout.payment.success');

Route::get(
    '/checkout/payment/{order:order_number}/cancelled',
    [PayMongoPaymentController::class, 'cancelled'],
)
    ->middleware('signed')
    ->name('checkout.payment.cancelled');

Route::post(
    '/checkout/payment/{order:order_number}/resume',
    [PayMongoPaymentController::class, 'resume'],
)
    ->middleware('throttle:checkout')
    ->name('checkout.payment.resume');

Route::middleware([
    'auth',
    'verified',
])->group(function (): void {
    Route::get(
        '/dashboard',
        AccountDashboardController::class,
    )->name('dashboard');

    Route::get(
        '/account/orders',
        [AccountOrderController::class, 'index'],
    )->name('account.orders.index');

    Route::get(
        '/account/orders/{order}',
        [AccountOrderController::class, 'show'],
    )->name('account.orders.show');

    Route::get(
        '/account/addresses',
        [AccountAddressController::class, 'index'],
    )->name('account.addresses.index');

    Route::post(
        '/account/addresses',
        [AccountAddressController::class, 'store'],
    )->name('account.addresses.store');

    Route::get(
        '/account/addresses/{address}/edit',
        [AccountAddressController::class, 'edit'],
    )->name('account.addresses.edit');

    Route::patch(
        '/account/addresses/{address}',
        [AccountAddressController::class, 'update'],
    )->name('account.addresses.update');

    Route::delete(
        '/account/addresses/{address}',
        [AccountAddressController::class, 'destroy'],
    )->name('account.addresses.destroy');
});

require __DIR__.'/settings.php';

// Keep this constrained catch-all route last.
Route::get(
    '/{page:slug}',
    ContentPageController::class,
)
    ->where(
        'page',
        implode(
            '|',
            Page::publicSlugs(),
        ),
    )
    ->name('pages.show');
