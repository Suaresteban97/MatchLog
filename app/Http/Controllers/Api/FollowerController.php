<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Follower\FollowRequest;

class FollowerController extends Controller
{
    /**
     * Get authenticated user's followers.
     */
    public function followers(Request $request)
    {
        $followers = $request->user()->followers()
            ->select('users.id', 'users.name', 'users.email')
            ->get();

        return response()->json(['followers' => $followers], 200);
    }

    /**
     * Get users that the authenticated user is following.
     */
    public function following(Request $request)
    {
        $following = $request->user()->following()
            ->select('users.id', 'users.name', 'users.email')
            ->get();

        return response()->json(['following' => $following], 200);
    }

    /**
     * Follow a user.
     */
    public function follow(FollowRequest $request)
    {
        $userId = $request->validated()['user_id'];

        if ($userId == $request->user()->id) {
            return response()->json(['error' => 'No puedes seguirte a ti mismo'], 400);
        }

        // Check if already following
        if ($request->user()->following()->where('following_id', $userId)->exists()) {
            return response()->json(['message' => 'Ya sigues a este usuario'], 200);
        }

        $request->user()->following()->attach($userId);

        return response()->json(['message' => 'Ahora sigues a este usuario'], 201);
    }

    /**
     * Unfollow a user.
     */
    public function unfollow(FollowRequest $request)
    {
        $userId = $request->validated()['user_id'];

        if (!$request->user()->following()->where('following_id', $userId)->exists()) {
            return response()->json(['error' => 'No sigues a este usuario'], 400);
        }

        $request->user()->following()->detach($userId);

        return response()->json(['message' => 'Dejaste de seguir a este usuario'], 200);
    }

    /**
     * Check if authenticated user follows a specific user.
     */
    public function isFollowing(Request $request, $userId)
    {
        $isFollowing = $request->user()->following()->where('following_id', $userId)->exists();

        return response()->json(['is_following' => $isFollowing], 200);
    }
}
