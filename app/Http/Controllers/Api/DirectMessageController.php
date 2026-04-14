<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\Friendship;
use App\Models\User;
use App\Events\DirectMessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class DirectMessageController extends Controller
{
    /**
     * Helper to verify if two users are friends (status: accepted)
     */
    private function areFriends($userId, $friendId)
    {
        return Friendship::where(function ($query) use ($userId, $friendId) {
            $query->where('user_id', $userId)->where('friend_id', $friendId);
        })->orWhere(function ($query) use ($userId, $friendId) {
            $query->where('user_id', $friendId)->where('friend_id', $userId);
        })->where('status', 'accepted')->exists();
    }

    /**
     * Helper to get or create a conversation.
     * Guarantees user1_id < user2_id to prevent duplicates.
     */
    private function getOrCreateConversation($userId, $friendId)
    {
        $u1 = min($userId, $friendId);
        $u2 = max($userId, $friendId);

        return Conversation::firstOrCreate([
            'user1_id' => $u1,
            'user2_id' => $u2,
        ]);
    }

    /**
     * Get the inbox / list of conversations for the user.
     */
    public function getConversations(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = Conversation::where('user1_id', $userId)
            ->orWhere('user2_id', $userId)
            ->with(['user1:id,name', 'user2:id,name'])
            ->with(['messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->get();

        $inbox = $conversations->map(function ($conv) use ($userId) {
            $otherUser = $conv->user1_id == $userId ? $conv->user2 : $conv->user1;
            $lastMessage = $conv->messages->first();

            $unreadCount = $conv->messages()
                ->where('sender_id', '!=', $userId)
                ->where('is_read', false)
                ->count();

            return [
                'conversation_id' => $conv->id,
                'friend' => [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                ],
                'last_message' => $lastMessage ? [
                    'message' => $lastMessage->message,
                    'is_mine' => $lastMessage->sender_id == $userId,
                    'created_at' => $lastMessage->created_at,
                ] : null,
                'unread_count' => $unreadCount,
            ];
        })
            ->sortByDesc(function ($item) {
                return $item['last_message'] ? $item['last_message']['created_at'] : null;
            })
            ->values();

        return response()->json(['conversations' => $inbox], 200);
    }

    /**
     * Get paginated messages for a specific friend.
     */
    public function getMessages(Request $request, $friendId)
    {
        $userId = $request->user()->id;

        if (!$this->areFriends($userId, $friendId)) {
            return response()->json(['error' => 'Solo puedes iniciar chat con tus amigos'], 403);
        }

        $conversation = $this->getOrCreateConversation($userId, $friendId);

        $messages = DirectMessage::with(['repliedMessage' => function ($query) {
            $query->select('id', 'message', 'sender_id');
        }])
            ->where('conversation_id', $conversation->id)
            ->latest()
            ->paginate(30);

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => $messages
        ], 200);
    }

    /**
     * Send a direct message to a friend.
     */
    public function sendMessage(Request $request, $friendId)
    {
        $userId = $request->user()->id;

        if (!$this->areFriends($userId, $friendId)) {
            return response()->json(['error' => 'Solamente puedes mensajear a tus amigos'], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'reply_to_id' => 'nullable|exists:direct_messages,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $conversation = $this->getOrCreateConversation($userId, $friendId);

        $replyToId = $request->reply_to_id;
        if ($replyToId) {
            $replyMsg = DirectMessage::find($replyToId);
            if (!$replyMsg || $replyMsg->conversation_id !== $conversation->id) {
                return response()->json(['error' => 'El mensaje al que intentas responder no es válido en esta conversación'], 400);
            }
        }

        $message = DirectMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $userId,
            'reply_to_id' => $replyToId,
            'message' => $request->message,
            'is_read' => false,
        ]);

        // Touch the conversation updated_at so it bumps in the inbox sorting
        $conversation->touch();

        // Load the reply context to return to frontend
        $message->load('repliedMessage:id,message,sender_id');

        // Broadcast the real-time event via WebSocket
        broadcast(new DirectMessageSent($message))->toOthers();

        return response()->json([
            'message' => 'Mensaje enviado',
            'data' => $message
        ], 201);
    }

    /**
     * Mark all messages in a conversation with a friend as read.
     */
    public function markAsRead(Request $request, $friendId)
    {
        $userId = $request->user()->id;

        $u1 = min($userId, $friendId);
        $u2 = max($userId, $friendId);

        $conversation = Conversation::where('user1_id', $u1)
            ->where('user2_id', $u2)
            ->first();

        if (!$conversation) {
            return response()->json(['message' => 'No hay conversación activa'], 200);
        }

        DirectMessage::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Mensajes marcados como leídos'], 200);
    }
}
