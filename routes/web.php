<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PosController;

use Inertia\Inertia;

Route::get('/', [MenuController::class, 'indexPage'])->name('home');
Route::get('/scan/{tableNumber}', [MenuController::class, 'scanTable'])->name('menu.scan');
Route::get('/cart', function () {
    return Inertia::render('Cart');
})->name('cart');

Route::get('/orders', function () {
    return Inertia::render('Orders');
})->name('orders');

Route::get('/order', function () {
    return Inertia::render('Orders');
});

Route::get('/reservation', function () {
    return Inertia::render('Reservation');
})->name('reservation');

Route::middleware('auth')->prefix('admin/pos-api')->group(function () {
    Route::get('/menus', [PosController::class, 'menus']);
    Route::get('/tables', [PosController::class, 'tables']);
    Route::get('/orders/active', [PosController::class, 'activeOrders']);
    Route::post('/orders/scan', [PosController::class, 'scanOrder']);
    Route::get('/orders/{order}', [PosController::class, 'showOrder']);
    Route::post('/orders/manual', [PosController::class, 'createManualOrder']);
    Route::post('/orders/{order}/promo', [PosController::class, 'applyPromo']);
    Route::delete('/orders/{order}/promo', [PosController::class, 'removePromo']);
    Route::post('/orders/{order}/payments/cash', [PosController::class, 'payCash']);
    Route::post('/orders/{order}/payments/snap', [PosController::class, 'createSnapToken']);
    Route::post('/orders/{order}/payments/mark-completed', [PosController::class, 'markDigitalPaid']);
});

