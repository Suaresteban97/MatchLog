<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Frontend\LoginController;
use App\Http\Controllers\Frontend\MySpaceController;
use App\Http\Controllers\Frontend\DashboardController;
use App\Http\Controllers\Frontend\DeviceController;
use App\Http\Controllers\Frontend\ProfileController;
use App\Http\Controllers\Frontend\CatalogController;

// ...

Route::get('/login', [LoginController::class, 'index'])->name('loginView');

Route::get('/', function () {
    return redirect()->route('loginView');
});

Route::middleware(['web.auth'])->group(function () {
    /**
     * Protected Frontend Routes
     */
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/my-space', [MySpaceController::class, 'index']);

    /**
     * Catalog (Games)
     */
    Route::get('/games', [CatalogController::class, 'index']);
    Route::get('/games/{slug}', [CatalogController::class, 'show']);

    /**
     * Devices & Profile
     */
    Route::get('/devices', [DeviceController::class, 'index']);
    Route::get('/devices/create', [DeviceController::class, 'create']);
    Route::get('/devices/{id}/edit', [DeviceController::class, 'edit']);
    Route::get('/profile', [ProfileController::class, 'edit']);
});
