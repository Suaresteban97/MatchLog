<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use App\Models\GameSessionMessage;
use App\Events\MessageSent;
use Illuminate\Http\Request;

class GameSessionChatController extends Controller
{
    /**
     * Get chat history for a session
     */
    public function index(Request $request, $id)
    {
        $session = GameSession::findOrFail($id);

        $this->authorizeAccess($request->user(), $session);

        $messages = $session->messages()->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return response()->json($messages);
    }

    /**
     * Store and broadcast a new message
     */
    public function store(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        $session = GameSession::findOrFail($id);

        $this->authorizeAccess($request->user(), $session);

        $message = $session->messages()->create([
            'user_id' => $request->user()->id,
            'session_id' => $session->id,
            'message' => $request->message
        ]);

        $message->load('user:id,name,email');

        // Broadcast to WebSockets
        broadcast(new MessageSent($message));

        return response()->json($message, 201);
    }

    /**
     * Ensure only hosts and active participants can read/write in the chat
     */
    private function authorizeAccess($user, $session)
    {
        if ($session->host_id !== $user->id && !$session->participants()->where('user_id', $user->id)->exists()) {
            abort(403, 'No tienes acceso al chat de esta sesión porque no eres participante.');
        }
    }
}
