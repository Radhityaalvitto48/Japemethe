<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MenuController;

use Inertia\Inertia;

Route::get('/', [MenuController::class, 'indexPage'])->name('home');
Route::get('/scan/{tableNumber}', [MenuController::class, 'scanTable'])->name('menu.scan');
Route::get('/cart', function () {
    return Inertia::render('Cart');
})->name('cart');

Route::get('/orders', function () {
    return Inertia::render('Orders');
})->name('orders');

Route::get('/reservation', function () {
    return Inertia::render('Reservation');
})->name('reservation');

