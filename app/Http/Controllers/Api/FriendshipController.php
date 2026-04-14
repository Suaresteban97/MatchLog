<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FriendshipController extends Controller
{
    /**
     * Get the list of accepted friends.
     */
    public function index(Request $request)
    {
        $friends = $request->user()->friends->map(function ($friend) {
            return [
                'id' => $friend->id,
                'name' => $friend->name,
                'email' => $friend->email,
            ];
        });

        return response()->json(['friends' => $friends], 200);
    }

    /**
     * Get pending friend requests received by the user.
     */
    public function pending(Request $request)
    {
        $pending = Friendship::with('user:id,name,email')
            ->where('friend_id', $request->user()->id)
            ->where('status', 'pending')
            ->get();

        return response()->json(['pending_requests' => $pending], 200);
    }

    /**
     * Get friend requests sent by the user that are still pending.
     */
    public function sent(Request $request)
    {
        $sent = Friendship::with('friend:id,name,email')
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->get();

        return response()->json(['sent_requests' => $sent], 200);
    }

    /**
     * Send a friend request.
     */
    public function sendRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'friend_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = $request->user()->id;
        $friendId = $request->friend_id;

        if ($userId == $friendId) {
            return response()->json(['error' => 'No puedes enviarte una solicitud a ti mismo'], 400);
        }

        // Check if there's any existing request
        $existing = Friendship::where(function ($query) use ($userId, $friendId) {
            $query->where('user_id', $userId)->where('friend_id', $friendId);
        })->orWhere(function ($query) use ($userId, $friendId) {
            $query->where('user_id', $friendId)->where('friend_id', $userId);
        })->first();

        if ($existing) {
            if ($existing->status === 'accepted') {
                return response()->json(['message' => 'Ya son amigos'], 400);
            }
            if ($existing->status === 'pending') {
                if ($existing->user_id == $userId) {
                    return response()->json(['message' => 'Ya enviaste una solicitud a este usuario'], 400);
                } else {
                    return response()->json(['message' => 'Este usuario ya te envió una solicitud. Por favor acéptala.'], 400);
                }
            }
            
            // If rejected, we allow to send again by updating the existing record
            $existing->update([
                'user_id' => $userId, // the new sender
                'friend_id' => $friendId, // the new receiver
                'status' => 'pending'
            ]);

            return response()->json(['message' => 'Solicitud enviada', 'data' => $existing], 201);
        }

        $friendship = Friendship::create([
            'user_id' => $userId,
            'friend_id' => $friendId,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Solicitud enviada', 'data' => $friendship], 201);
    }

    /**
     * Accept a friend request.
     */
    public function acceptRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'friend_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = $request->user()->id;
        $requesterId = $request->friend_id;

        $friendship = Friendship::where('user_id', $requesterId)
            ->where('friend_id', $userId)
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return response()->json(['error' => 'No hay solicitud pendiente de este usuario'], 404);
        }

        $friendship->update(['status' => 'accepted']);

        return response()->json(['message' => 'Solicitud aceptada', 'data' => $friendship], 200);
    }

    /**
     * Reject a friend request.
     */
    public function rejectRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'friend_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = $request->user()->id;
        $requesterId = $request->friend_id;

        $friendship = Friendship::where('user_id', $requesterId)
            ->where('friend_id', $userId)
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return response()->json(['error' => 'No hay solicitud pendiente de este usuario'], 404);
        }

        $friendship->update(['status' => 'rejected']);

        return response()->json(['message' => 'Solicitud rechazada', 'data' => $friendship], 200);
    }

    /**
     * Remove a friend.
     */
    public function removeFriend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'friend_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = $request->user()->id;
        $friendId = $request->friend_id;

        $friendship = Friendship::where(function ($query) use ($userId, $friendId) {
            $query->where('user_id', $userId)->where('friend_id', $friendId);
        })->orWhere(function ($query) use ($userId, $friendId) {
            $query->where('user_id', $friendId)->where('friend_id', $userId);
        })->where('status', 'accepted')->first();

        if (!$friendship) {
            return response()->json(['error' => 'Este usuario no es tu amigo'], 404);
        }

        $friendship->delete();

        return response()->json(['message' => 'Amigo eliminado correctamente'], 200);
    }
}
