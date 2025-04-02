<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Controllers
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

//Middlware
use App\Http\Middleware\ApiMiddleware;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware([ApiMiddleware::class])->group(function () {

    /**
     * Logout
     */
    Route::post('/logout', [AuthController::class, 'logout']);

    /**
     * Users Methods
     */
    Route::resource('users', UserController::class);

    
});

//Google Auth
Route::post('/store-google-token', [AuthController::class, 'storeGoogleToken']);