<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\GameSessionParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GameSessionService
{
    public function getHostingSessions(User $user)
    {
        return $user->sessionsHosting()->with('participants', 'host')->get();
    }

    public function getParticipatingSessions(User $user)
    {
        return $user->sessionsParticipating()->with('host')->get();
    }

    public function createSession(User $user, array $data)
    {
        $session = $user->sessionsHosting()->create($data);
        return $session->load('host');
    }

    public function getSession($id)
    {
        return GameSession::with('host', 'participants')->findOrFail($id);
    }

    public function updateSession(User $user, $id, array $data)
    {
        $session = $user->sessionsHosting()->findOrFail($id);
        $session->update($data);
        return $session->load('host', 'participants');
    }

    public function deleteSession(User $user, $id)
    {
        $session = $user->sessionsHosting()->findOrFail($id);

        // Optional: Notify participants?

        return $session->delete();
    }

    public function joinSession(User $user, $sessionId)
    {
        $session = GameSession::findOrFail($sessionId);

        // Check if already participating
        $existing = GameSessionParticipant::where('game_session_id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return ['status' => 'info', 'message' => 'Ya estás participando en esta sesión'];
        }

        // Check if session is full
        $currentParticipants = $session->participants()->count();
        if ($currentParticipants >= $session->max_participants) {
            return ['status' => 'error', 'message' => 'La sesión está llena', 'code' => 400];
        }

        GameSessionParticipant::create([
            'game_session_id' => $sessionId,
            'user_id' => $user->id,
            'status' => 'accepted'
        ]);

        return ['status' => 'success', 'message' => 'Te uniste a la sesión correctamente'];
    }

    public function leaveSession(User $user, $sessionId)
    {
        $participant = GameSessionParticipant::where('game_session_id', $sessionId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $participant->delete();

        return true;
    }

    public function browseSessions()
    {
        return GameSession::with('host')
            ->where('status', 'scheduled')
            ->where('start_time', '>', now())
            ->orderBy('start_time', 'asc')
            ->paginate(20);
    }
}
