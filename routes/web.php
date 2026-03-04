<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MenuController;

use Inertia\Inertia;

Route::get('/', [MenuController::class, 'index'])->name('menu.index');

;
