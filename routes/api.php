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
use App\Http\Controllers\Api\FriendshipController;
use App\Http\Controllers\Api\DirectMessageController;
use App\Http\Controllers\Api\GameSessionController;
use App\Http\Controllers\Api\ExecutionPlatformController;
use App\Http\Controllers\Api\SocialProfileController;
use App\Http\Controllers\Api\GamesController;
use App\Http\Controllers\Api\RawgController;
use App\Http\Controllers\Api\ContributionController;
use App\Http\Controllers\Api\Admin\ModerationController;

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

    /**
     * RAWG API sync (Admin only)
     */
    Route::post('/admin/rawg/sync-list', [RawgController::class, 'syncList']);
    Route::post('/admin/rawg/sync-detail/{slug}', [RawgController::class, 'syncDetail']);
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
     * Collections Methods
     */
    Route::resource('collections', \App\Http\Controllers\Api\CollectionController::class);
    Route::post('collections/{collection}/games/{game}', [\App\Http\Controllers\Api\CollectionController::class, 'addGame']);
    Route::delete('collections/{collection}/games/{game}', [\App\Http\Controllers\Api\CollectionController::class, 'removeGame']);

    /**
     * Admin (Moderation Panel)
     */
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/contributions', [ModerationController::class, 'index']);
        Route::post('/admin/contributions/{id}/resolve', [ModerationController::class, 'resolve']);
    });

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
     * Friendships
     */
    Route::get('/friends', [FriendshipController::class, 'index']);
    Route::get('/friends/pending', [FriendshipController::class, 'pending']);
    Route::get('/friends/sent', [FriendshipController::class, 'sent']);
    Route::post('/friends/request', [FriendshipController::class, 'sendRequest']);
    Route::post('/friends/accept', [FriendshipController::class, 'acceptRequest']);
    Route::post('/friends/reject', [FriendshipController::class, 'rejectRequest']);
    Route::post('/friends/remove', [FriendshipController::class, 'removeFriend']);

    /**
     * Direct Messages (1-on-1 Chat)
     */
    Route::get('/chats', [DirectMessageController::class, 'getConversations']);
    Route::get('/chats/user/{friendId}', [DirectMessageController::class, 'getMessages']);
    Route::post('/chats/user/{friendId}', [DirectMessageController::class, 'sendMessage']);
    Route::put('/chats/user/{friendId}/read', [DirectMessageController::class, 'markAsRead']);

    /**
     * Game Sessions
     */
    Route::get('/sessions/browse', [GameSessionController::class, 'browse']);
    Route::post('/sessions/{id}/join', [GameSessionController::class, 'join']);
    Route::post('/sessions/{id}/leave', [GameSessionController::class, 'leave']);
    Route::apiResource('sessions', GameSessionController::class);

    // Chat endpoints
    Route::get('/sessions/{id}/messages', [App\Http\Controllers\Api\GameSessionChatController::class, 'index']);
    Route::post('/sessions/{id}/messages', [App\Http\Controllers\Api\GameSessionChatController::class, 'store']);

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
    Route::get('/games/{id}/reviews', [GamesController::class, 'reviews']); // Infinite scroll reviews
    Route::get('/games-metadata', [GamesController::class, 'metadata']);
    Route::get('/my-games', [GamesController::class, 'getUserGames']);
    Route::put('/my-games/{id}', [GamesController::class, 'updateUserGame']);
    Route::post('/my-games/{id}/toggle', [GamesController::class, 'toggleUserGame']);
    Route::patch('/my-games/{id}/status', [GamesController::class, 'changeStatus']);

    /**
     * Community Contributions
     */
    Route::post('/contributions', [ContributionController::class, 'store']);
    Route::get('/contributions', [ContributionController::class, 'index']);
    Route::get('/contributions/resource/{type}/{id}', [ContributionController::class, 'forResource']);
});

//Google Auth
Route::post('/store-google-token', [GoogleAuthController::class, 'storeGoogleToken']);
