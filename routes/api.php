<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Controllers
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\FollowerController;
use App\Http\Controllers\Api\GameSessionController;
use App\Http\Controllers\Api\ExecutionPlatformController;
use App\Http\Controllers\Api\SocialProfileController;
use App\Http\Controllers\Api\GamesController;

//Middlware
use App\Http\Middleware\ApiMiddleware;
use App\Http\Middleware\AdminMiddleware;

//Métodos de autenticación
Route::middleware([AdminMiddleware::class])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    /**
     * Games (Admin overrides)
     */
    Route::post('games', [GamesController::class, 'store']);
    Route::put('games/{game}', [GamesController::class, 'update']);
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
    Route::get('/devices/{id}', [DeviceController::class, 'show']);
    Route::post('/devices', [DeviceController::class, 'store']);
    Route::put('/devices/{id}', [DeviceController::class, 'update']);
    Route::delete('/devices/{id}', [DeviceController::class, 'destroy']);

    /**
     * Catalog
     */
    Route::get('/catalog', [CatalogController::class, 'index']);

    /**
     * User Profile
     */
    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::put('/profile', [UserProfileController::class, 'update']);

    /**
     * Social Profiles
     */
    Route::get('/social-platforms', [SocialProfileController::class, 'platforms']);
    Route::apiResource('social-profiles', SocialProfileController::class);

    /**
     * Followers
     */
    Route::get('/followers', [FollowerController::class, 'followers']);
    Route::get('/following', [FollowerController::class, 'following']);
    Route::post('/follow', [FollowerController::class, 'follow']);
    Route::post('/unfollow', [FollowerController::class, 'unfollow']);
    Route::get('/is-following/{userId}', [FollowerController::class, 'isFollowing']);

    /**
     * Game Sessions
     */
    Route::get('/sessions/browse', [GameSessionController::class, 'browse']);
    Route::post('/sessions/{id}/join', [GameSessionController::class, 'join']);
    Route::post('/sessions/{id}/leave', [GameSessionController::class, 'leave']);
    Route::apiResource('sessions', GameSessionController::class);

    /**
     * Execution Platforms
     */
    Route::get('/execution-platforms', [ExecutionPlatformController::class, 'index']);
    Route::get('/my-execution-platforms', [ExecutionPlatformController::class, 'userPlatforms']);
    Route::post('/execution-platforms/attach', [ExecutionPlatformController::class, 'attach']);
    Route::post('/execution-platforms/detach', [ExecutionPlatformController::class, 'detach']);

    /**
     * Games
     */
    Route::get('/games', [GamesController::class, 'getGames']);
    Route::get('/games/{id}', [GamesController::class, 'show']);
    Route::get('/games-metadata', [GamesController::class, 'metadata']);
    Route::get('/my-games', [GamesController::class, 'getUserGames']);
    Route::put('/my-games/{id}', [GamesController::class, 'updateUserGame']);
    Route::post('/my-games/{id}/toggle', [GamesController::class, 'toggleUserGame']);
    Route::patch('/my-games/{id}/status', [GamesController::class, 'changeStatus']);
});

//Google Auth
Route::post('/store-google-token', [GoogleAuthController::class, 'storeGoogleToken']);
