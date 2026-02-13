<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use App\Models\GameSessionParticipant;
use Illuminate\Http\Request;
use App\Http\Requests\GameSession\StoreGameSessionRequest;
use App\Http\Requests\GameSession\UpdateGameSessionRequest;

class GameSessionController extends Controller
{
    /**
     * Display a listing of game sessions (hosted by user + participating).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $hosting = $user->sessionsHosting()->with('participants', 'host')->get();
        $participating = $user->sessionsParticipating()->with('host')->get();

        return response()->json([
            'hosting' => $hosting,
            'participating' => $participating
        ], 200);
    }

    /**
     * Store a newly created game session.
     */
    public function store(StoreGameSessionRequest $request)
    {
        $session = $request->user()->sessionsHosting()->create($request->validated());
        $session->load('host');

        return response()->json(['session' => $session], 201);
    }

    /**
     * Display the specified game session.
     */
    public function show(Request $id)
    {
        $session = GameSession::with('host', 'participants')->findOrFail($id);

        return response()->json(['session' => $session], 200);
    }

    /**
     * Update the specified game session (only host can update).
     */
    public function update(UpdateGameSessionRequest $request, $id)
    {
        $session = $request->user()->sessionsHosting()->findOrFail($id);
        $session->update($request->validated());
        $session->load('host', 'participants');

        return response()->json(['session' => $session], 200);
    }

    /**
     * Delete the game session (only host can delete).
     */
    public function destroy(Request $request, $id)
    {
        $session = $request->user()->sessionsHosting()->findOrFail($id);
        $session->delete();

        return response()->json(['message' => 'Sesión eliminada correctamente'], 200);
    }

    /**
     * Join a game session.
     */
    public function join(Request $request, $id)
    {
        $session = GameSession::findOrFail($id);

        // Check if already participating
        $existing = GameSessionParticipant::where('game_session_id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Ya estás participando en esta sesión'], 200);
        }

        // Check if session is full
        $currentParticipants = $session->participants()->count();
        if ($currentParticipants >= $session->max_participants) {
            return response()->json(['error' => 'La sesión está llena'], 400);
        }

        GameSessionParticipant::create([
            'game_session_id' => $id,
            'user_id' => $request->user()->id,
            'status' => 'accepted'
        ]);

        return response()->json(['message' => 'Te uniste a la sesión correctamente'], 201);
    }

    /**
     * Leave a game session.
     */
    public function leave(Request $request, $id)
    {
        $participant = GameSessionParticipant::where('game_session_id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $participant->delete();

        return response()->json(['message' => 'Abandonaste la sesión'], 200);
    }

    /**
     * List all open/public game sessions.
     */
    public function browse(Request $request)
    {
        $sessions = GameSession::with('host')
            ->where('status', 'scheduled')
            ->where('start_time', '>', now())
            ->orderBy('start_time', 'asc')
            ->paginate(20);

        return response()->json($sessions, 200);
    }
}
