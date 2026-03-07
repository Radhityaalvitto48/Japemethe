<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MenuController;

use Inertia\Inertia;

Route::get('/', [MenuController::class, 'index'])->name('menu.index');
Route::get('/scan/{tableNumber}', [MenuController::class, 'scanTable'])->name('menu.scan');
Route::get('/cart', function () {
    return Inertia::render('Cart');
})->name('cart');

