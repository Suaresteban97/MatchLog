<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Controllers
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\GoogleAuthController;

//Middlware
use App\Http\Middleware\ApiMiddleware;
use App\Http\Middleware\AdminMiddleware;

//Métodos de autenticación
Route::middleware([AdminMiddleware::class])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

//Métodos de recuperación de contraseña
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

    /**
     * Devices
     */
    Route::get('/devices', [DeviceController::class, 'index']);
    Route::post('/devices', [DeviceController::class, 'store']);
    Route::put('/devices/{id}', [DeviceController::class, 'update']);
    Route::delete('/devices/{id}', [DeviceController::class, 'destroy']);
    
    /**
     * User Profile
     */
    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::put('/profile', [UserProfileController::class, 'update']);
    
});

//Google Auth
Route::post('/store-google-token', [GoogleAuthController::class, 'storeGoogleToken']);