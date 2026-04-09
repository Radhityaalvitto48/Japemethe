<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PosController;
use App\Models\Table;

use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Reservation');
})->name('home');
Route::get('/s/{scanHash}', [MenuController::class, 'scanTable'])
    ->middleware('throttle:scan')
    ->where('scanHash', '[A-Za-z0-9_-]{4,128}')
    ->name('menu.scan');

Route::get('/scan/{tableNumber}', function (string $tableNumber) {
    $table = Table::query()
        ->where('table_number', $tableNumber)
        ->where('is_active', true)
        ->firstOrFail();

    return redirect()->route('menu.scan', ['scanHash' => Table::encodeScanToken($table->table_number)], 301);
})->middleware('throttle:scan');
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

Route::middleware('auth')->get('/admin/kasir-app', function () {
    abort_unless(in_array(Auth::user()?->role, ['admin', 'kasir'], true), 403);
    return view('admin.kasir-app');
})->name('admin.kasir-app');

Route::middleware('auth')->prefix('admin/pos-api')->group(function () {
    Route::get('/menus', [PosController::class, 'menus']);
    Route::get('/tables', [PosController::class, 'tables']);
    Route::get('/orders/active', [PosController::class, 'activeOrders']);
    Route::get('/reservations/pending', [PosController::class, 'pendingReservations']);
    Route::post('/reservations/{reservation}/confirm', [PosController::class, 'confirmReservation']);
    Route::post('/orders/scan', [PosController::class, 'scanOrder']);
    Route::get('/orders/{order}', [PosController::class, 'showOrder']);
    Route::post('/orders/manual', [PosController::class, 'createManualOrder']);
    Route::post('/orders/{order}/items', [PosController::class, 'addOrderItem']);
    Route::patch('/orders/{order}/items/{detail}', [PosController::class, 'updateOrderItem']);
    Route::post('/orders/{order}/promo', [PosController::class, 'applyPromo']);
    Route::delete('/orders/{order}/promo', [PosController::class, 'removePromo']);
    Route::post('/orders/{order}/payments/cash', [PosController::class, 'payCash']);
    Route::post('/orders/{order}/payments/mark-completed', [PosController::class, 'markDigitalPaid']);
});

