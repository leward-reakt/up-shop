<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)
    ->name('home');

Route::get('/shop', [ShopController::class, 'index'])
    ->name('shop.index');

Route::get('/products/{product:slug}', [ShopController::class, 'show'])
    ->name('products.show');

Route::get('/cart', [CartController::class, 'index'])
    ->name('cart.index');

Route::post('/cart/items', [CartController::class, 'store'])
    ->name('cart.items.store');

Route::patch('/cart/items/{product}', [CartController::class, 'update'])
    ->name('cart.items.update');

Route::delete('/cart/items/{productId}', [CartController::class, 'destroy'])
    ->name('cart.items.destroy');

Route::post('/cart/discount', [CartController::class, 'applyDiscount'])
    ->name('cart.discount.store');

Route::delete('/cart/discount', [CartController::class, 'removeDiscount'])
    ->name('cart.discount.destroy');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')
        ->name('dashboard');
});

require __DIR__.'/settings.php';
