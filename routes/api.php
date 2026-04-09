<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDetailController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\Api\CarouselController;



// Public API routes
// Menu & Table (read-only untuk user)
Route::middleware('throttle:public-api')->group(function () {
    Route::get('/menus', [MenuController::class, 'index']);
    Route::get('/menus/{id}', [MenuController::class, 'show']);
    Route::get('/tables', [TableController::class, 'index']);
    Route::get('/tables/{tableNumber}', [TableController::class, 'showByNumber']);

    // Carousel (read-only untuk user)
    Route::get('/carousels/active', [CarouselController::class, 'active']);
});

// Promo validation
Route::middleware('throttle:write-api')->post('/promos/validate', [PromoController::class, 'validateCode']);

// Orders
Route::prefix('orders')->middleware('throttle:write-api')->group(function () {
    Route::post('/', [OrderController::class, 'store']);
    Route::post('/by-ids', [OrderController::class, 'getByIds']);
    Route::post('/scan', [OrderController::class, 'scanQr'])->middleware('throttle:scan');
    Route::post('/{id}/apply-promo', [OrderController::class, 'applyPromo']);
    Route::get('/{orderNumber}', [OrderController::class, 'showByNumber']);
    Route::put('/{id}/status', [OrderController::class, 'updateStatus']);
});

// Order Details
Route::prefix('order-details')->middleware('throttle:write-api')->group(function () {
    Route::post('/', [OrderDetailController::class, 'store']);
    Route::get('/order/{orderId}', [OrderDetailController::class, 'getByOrder']);
});

// Payments
Route::prefix('payments')->middleware('throttle:write-api')->group(function () {
    Route::post('/', [PaymentController::class, 'store']);
    Route::get('/order/{orderId}', [PaymentController::class, 'getByOrder']);
    Route::post('/order/{id}/snap-token', [PaymentController::class, 'createSnapTokenForOrder']);
    Route::post('/order/{id}/cash', [PaymentController::class, 'payCash']);
    Route::put('/{id}/status', [PaymentController::class, 'updateStatus']);
    Route::post('/notification', [PaymentController::class, 'handleNotification'])->middleware('throttle:payment-callback'); // Midtrans callback
});

// POS endpoints (cashier flow)
Route::prefix('pos')->middleware('throttle:write-api')->group(function () {
    Route::post('/scan', [OrderController::class, 'scanQr'])->middleware('throttle:scan');
    Route::post('/orders/{id}/apply-promo', [OrderController::class, 'applyPromo']);
    Route::post('/orders/{id}/payment/snap-token', [PaymentController::class, 'createSnapTokenForOrder']);
    Route::post('/orders/{id}/payment/cash', [PaymentController::class, 'payCash']);
});

// Reservations
Route::prefix('reservations')->middleware('throttle:write-api')->group(function () {
    Route::post('/', [ReservationController::class, 'store']);
    Route::get('/{id}', [ReservationController::class, 'show']);
    Route::put('/{id}/cancel', [ReservationController::class, 'cancel']);
});
