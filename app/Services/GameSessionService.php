<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\GameSessionParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameSessionService
{
    public function getHostingSessions(User $user)
    {
        return $user->sessionsHosting()->with('participants', 'host')->get();
    }

    public function getParticipatingSessions(User $user)
    {
        return $user->sessionsParticipating()
            ->where('host_id', '!=', $user->id)
            ->with('host')
            ->get();
    }

    public function createSession(User $user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {
            $session = $user->sessionsHosting()->create($data);

            GameSessionParticipant::create([
                'game_session_id' => $session->id,
                'user_id'         => $user->id,
                'status'          => 'accepted',
            ]);

            return $session->load('host', 'participants');
        });
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
        return DB::transaction(function () use ($user, $id) {
            $session = $user->sessionsHosting()->findOrFail($id);

            // Detach all participants to avoid orphaned pivot rows
            $session->participants()->detach();

            return $session->delete();
        });
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
        $session = GameSession::findOrFail($sessionId);

        // If the user is the host, leaving = deleting the entire session
        if ($session->host_id === $user->id) {
            return DB::transaction(function () use ($session) {
                // IMPORTANT: Use detach() on BelongsToMany relations to delete pivot rows.
                // delete() would delete the related User models!
                $session->participants()->detach();
                $session->delete();
                return ['action' => 'deleted', 'message' => 'Eras el anfitrión: la sesión fue eliminada.'];
            });
        }

        // Regular participant: just remove their record
        $participant = GameSessionParticipant::where('game_session_id', $sessionId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $participant->delete();

        return ['action' => 'left', 'message' => 'Abandonaste la sesión.'];
    }

    public function browseSessions(User $user, array $filters = [])
    {
        // 1. Gather user preferences for scoring
        $userGameIds = DB::table('user_games')->where('user_id', $user->id)->pluck('game_id')->toArray();
        $userGenreIds = [];

        if (!empty($userGameIds)) {
            $userGenreIds = DB::table('game_genre')
                ->whereIn('game_id', $userGameIds)
                ->distinct()
                ->pluck('genre_id')
                ->toArray();
        }

        // Prepare safe arrays for raw queries
        $safeGameIds = empty($userGameIds) ? [0] : $userGameIds;
        $safeGenreIds = empty($userGenreIds) ? [0] : $userGenreIds;

        $gameBindings = implode(',', array_fill(0, count($safeGameIds), '?'));
        $genreBindings = implode(',', array_fill(0, count($safeGenreIds), '?'));
        $bindings = array_merge($safeGameIds, $safeGenreIds);

        // 2. Build Base Query with Filters
        $query = GameSession::select('game_sessions.*')
            ->selectRaw("
                (
                    CASE WHEN game_sessions.game_id IN ({$gameBindings}) THEN 50 ELSE 0 END +
                    CASE WHEN EXISTS (
                        SELECT 1 FROM game_genre 
                        WHERE game_genre.game_id = game_sessions.game_id 
                        AND game_genre.genre_id IN ({$genreBindings})
                    ) THEN 20 ELSE 0 END +
                    GREATEST(0, (game_sessions.max_participants - (
                        SELECT count(*) FROM game_session_participants 
                        WHERE game_session_participants.game_session_id = game_sessions.id
                    ))) * 5
                ) as recommendation_score
            ", $bindings)
            ->with(['host', 'game'])
            ->withCount('participants')
            ->where('host_id', '!=', $user->id)
            ->where('status', 'scheduled')
            ->where('start_time', '>=', now()->subHours(24));

        // Apply Filters
        if (!empty($filters['game_id'])) {
            $query->where('game_id', $filters['game_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('start_time', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('start_time', '<=', $filters['end_date']);
        }

        if (!empty($filters['available_only']) && filter_var($filters['available_only'], FILTER_VALIDATE_BOOLEAN)) {
            $query->havingRaw("game_sessions.max_participants > (SELECT count(*) FROM game_session_participants WHERE game_session_participants.game_session_id = game_sessions.id)");
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhereHas('host', function ($hq) use ($search) {
                        $hq->where('name', 'like', $search)
                            ->orWhere('nickname', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    });
            });
        }

        // 3. Sort by score then date
        $query->orderBy('recommendation_score', 'desc')
            ->orderBy('start_time', 'asc');

        Log::info('SQL: ' . $query->toSql());
        Log::info('Bindings: ', $query->getBindings());

        $paginated = $query->paginate(20);

        // 4. Transform results to append is_recommended flag
        $paginated->getCollection()->transform(function ($session) {
            // A session is considered recommended if it matched a game or genre (score >= 20)
            // and has spots available
            $session->is_recommended = $session->recommendation_score >= 20 && ($session->max_participants > $session->participants_count);
            return $session;
        });

        return $paginated;
    }
}
