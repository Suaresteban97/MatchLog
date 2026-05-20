<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Frontend\LoginController;
use App\Http\Controllers\Frontend\MySpaceController;
use App\Http\Controllers\Frontend\DashboardController;
use App\Http\Controllers\Frontend\DeviceController;
use App\Http\Controllers\Frontend\ProfileController;
use App\Http\Controllers\Frontend\CatalogController;
use App\Http\Controllers\Frontend\WelcomeController;
use App\Http\Controllers\Frontend\Admin\ModerationViewController;

// Root: landing if guest, dashboard if authenticated
Route::get('/', function () {
    $token = request()->cookie('auth_token');
    if ($token) {
        $user = \App\Models\User::where('api_token', hash('sha256', $token))
            ->where('token_expires_at', '>', now())
            ->first();
        if ($user) {
            return redirect('/dashboard');
        }
    }
    return app(WelcomeController::class)->index();
})->name('home');

// Public login
Route::get('/login', [LoginController::class, 'index'])->name('loginView');

// Public welcome page (direct URL)
Route::get('/welcome', [WelcomeController::class, 'index'])->name('welcome');

// Public Profile Route
Route::get('/user/{user:name}', [ProfileController::class, 'show'])->name('profile.show');

Route::middleware(['web.auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/my-space', [MySpaceController::class, 'index']);

    // Catalog (Games)
    Route::get('/games', [CatalogController::class, 'index']);
    Route::get('/games/{slug}', [CatalogController::class, 'show']);

    // Devices & Profile
    Route::get('/devices', [DeviceController::class, 'index']);
    Route::get('/devices/create', [DeviceController::class, 'create']);
    Route::get('/devices/{id}/edit', [DeviceController::class, 'edit']);
    Route::get('/profile', [ProfileController::class, 'edit']);

    // Admin
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/moderation', [ModerationViewController::class, 'index']);
    });
});
