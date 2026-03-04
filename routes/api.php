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
Route::get('/menus', [MenuController::class, 'index']);
Route::get('/menus/{id}', [MenuController::class, 'show']);
Route::get('/tables', [TableController::class, 'index']);
Route::get('/tables/{tableNumber}', [TableController::class, 'showByNumber']);

// Carousel (read-only untuk user)
Route::get('/carousels/active', [CarouselController::class, 'active']);

// Promo validation
Route::post('/promos/validate', [PromoController::class, 'validateCode']);

// Orders
Route::prefix('orders')->group(function () {
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/{orderNumber}', [OrderController::class, 'showByNumber']);
    Route::put('/{id}/status', [OrderController::class, 'updateStatus']);
});

// Order Details
Route::prefix('order-details')->group(function () {
    Route::post('/', [OrderDetailController::class, 'store']);
    Route::get('/order/{orderId}', [OrderDetailController::class, 'getByOrder']);
});

// Payments
Route::prefix('payments')->group(function () {
    Route::post('/', [PaymentController::class, 'store']);
    Route::get('/order/{orderId}', [PaymentController::class, 'getByOrder']);
    Route::put('/{id}/status', [PaymentController::class, 'updateStatus']);
    Route::post('/notification', [PaymentController::class, 'handleNotification']); // Midtrans callback
});

// Reservations
Route::prefix('reservations')->group(function () {
    Route::post('/', [ReservationController::class, 'store']);
    Route::get('/{id}', [ReservationController::class, 'show']);
    Route::put('/{id}/cancel', [ReservationController::class, 'cancel']);
});
