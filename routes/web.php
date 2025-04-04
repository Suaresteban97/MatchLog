<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Frontend\LoginController;

/**
 * Register & Login Views
 */
Route::get('/login', [LoginController::class, 'index']);
