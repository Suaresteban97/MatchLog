<?php

namespace App\Services;

use App\Models\Game;
use App\Models\User;
use App\Models\GameStatus;
use App\Models\UserGame;
use Illuminate\Support\Facades\DB;

class GameService
{
    /**
     * Get a paginated list of games with optional filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getGames(array $filters = [], int $perPage = 15)
    {
        $query = Game::with('genres');

        // Filter by name
        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        // Filter by developer
        if (!empty($filters['developer'])) {
            $query->where('developer', 'like', '%' . $filters['developer'] . '%');
        }

        // Filter by publisher
        if (!empty($filters['publisher'])) {
            $query->where('publisher', 'like', '%' . $filters['publisher'] . '%');
        }

        // Filter by year (release_year)
        if (!empty($filters['release_year'])) {
            $query->whereYear('release_date', $filters['release_year']);
        }

        // Filter by min metacritic score
        if (!empty($filters['metacritic_score'])) {
            $query->where('metacritic_score', '>=', (int) $filters['metacritic_score']);
        }

        // Filter by genre id
        if (!empty($filters['genre_id'])) {
            $query->whereHas('genres', function ($q) use ($filters) {
                $q->where('genres.id', $filters['genre_id']);
            });
        }

        // Filter by platform id
        if (!empty($filters['platform_id'])) {
            $query->whereHas('platforms', function ($q) use ($filters) {
                $q->where('game_platforms.id', $filters['platform_id']);
            });
        }

        // Filter by platform name (legacy / search string)
        if (!empty($filters['platform'])) {
            $query->whereHas('platforms', function ($q) use ($filters) {
                $q->where('game_platforms.name', 'like', '%' . $filters['platform'] . '%');
            });
        }

        // Boolean capability filters
        if (!empty($filters['is_multiplayer'])) {
            $query->where('is_multiplayer', true);
        }
        if (!empty($filters['is_cooperative'])) {
            $query->where('is_cooperative', true);
        }
        if (!empty($filters['is_online_multiplayer'])) {
            $query->where('is_online_multiplayer', true);
        }
        if (!empty($filters['is_local_multiplayer'])) {
            $query->where('is_local_multiplayer', true);
        }

        // Filter by having screenshots
        if (!empty($filters['has_screenshots'])) {
            $query->whereHas('screenshots');
        }

        // Sorting
        $allowedSortFields = ['metacritic_score', 'name', 'release_date', 'created_at'];
        $sortBy  = in_array($filters['sort_by'] ?? '', $allowedSortFields)
            ? $filters['sort_by']
            : 'metacritic_score';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        // Games without a score sink to the bottom regardless of direction
        $query->orderByRaw("CASE WHEN {$sortBy} IS NULL THEN 1 ELSE 0 END")
            ->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get games linked to the provided user.
     *
     * @param User $user
     * @return \Illuminate\Support\Collection
     */
    public function getUserGames(User $user, int $perPage = 15)
    {
        // Return UserGame models with eager-loaded relations
        return \App\Models\UserGame::with(['game', 'status', 'platform'])
            ->where('user_id', $user->id)
            ->paginate($perPage);
    }

    /**
     * Get a single game with all relationships by numeric ID or slug.
     *
     * @param int|string $identifier
     * @return Game
     */
    public function getGameWithRelations($identifier): Game
    {
        $query = Game::with(['genres', 'platforms', 'userGames.status', 'userGames.platform', 'screenshots']);

        if (is_numeric($identifier)) {
            $query->where('id', $identifier);
        } else {
            $query->where('slug', $identifier);
        }

        return $query->firstOrFail();
    }

    /**
     * Create a new game in the catalog.
     *
     * @param array $data
     * @return Game
     */
    public function storeGame(array $data): Game
    {
        $game = Game::create($data);

        if (isset($data['genres'])) {
            $game->genres()->attach($data['genres']);
        }

        if (isset($data['platforms'])) {
            $game->platforms()->attach($data['platforms']);
        }

        return $game->fresh(['genres', 'platforms']);
    }

    /**
     * Update a game's core information.
     *
     * @param Game $game
     * @param array $data
     * @return Game
     */
    public function updateGame(Game $game, array $data): Game
    {
        $game->update($data);

        // Optionally update genres / platforms if passed
        if (isset($data['genres'])) {
            $game->genres()->sync($data['genres']);
        }

        if (isset($data['platforms'])) {
            $game->platforms()->sync($data['platforms']); // Assumes array of IDs. Can pass withPivot for release_date
        }

        return $game->fresh(['genres', 'platforms']);
    }

    /**
     * Link or unlink a game to/from the user's library.
     * If the record for the specific platform exists, it toggles it.
     *
     * @param User $user
     * @param int $gameId
     * @param array $pivotData (e.g., ['game_platform_id' => 1])
     * @return array Response payload indicating action performed.
     */
    public function toggleUserGame(User $user, int $gameId, array $pivotData = [])
    {
        $game = Game::findOrFail($gameId);

        $platformId = $pivotData['game_platform_id'] ?? null;

        $query = DB::table('user_games')
            ->where('user_id', $user->id)
            ->where('game_id', $gameId);

        $existingLink = $query->first();

        if ($existingLink) {
            // Unlink
            DB::table('user_games')->where('id', $existingLink->id)->delete();
            return ['action' => 'unlinked', 'message' => 'Juego desvinculado de la biblioteca correctamente.'];
        } else {
            // Link
            $statusId = $pivotData['game_status_id'] ?? null;

            if (!$statusId) {
                $status = GameStatus::where('slug', 'backlog')->first();
                $statusId = $status ? $status->id : null;
            }

            // Determine is_currently_playing based on status
            $isPlaying = false;
            if ($statusId) {
                $linkedStatus = GameStatus::find($statusId);
                $isPlaying = $linkedStatus && $linkedStatus->slug === 'playing';
            }

            DB::table('user_games')->insert([
                'user_id' => $user->id,
                'game_id' => $gameId,
                'game_status_id' => $statusId,
                'game_platform_id' => $platformId,
                'is_currently_playing' => $isPlaying ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['action' => 'linked', 'message' => 'Juego añadido a la biblioteca correctamente.'];
        }
    }

    /**
     * Change the playing status of a game in the user's library.
     *
     * @param User $user
     * @param int $gameId
     * @param string $statusSlug (Playing, Completed, Backlog, Wishlist, Dropped, On Hold)
     * @return array
     */
    public function changeUserGameStatus(User $user, int $gameId, string $statusSlug)
    {
        $status = GameStatus::where('slug', $statusSlug)->firstOrFail();

        // Update the pivot table where user_id and game_id match.
        // If the user has multiple entries for the same game (different platforms),
        // we might update all of them or require the platform ID. We'll update all for now.
        $updatedRows = DB::table('user_games')
            ->where('user_id', $user->id)
            ->where('game_id', $gameId)
            ->update([
                'game_status_id' => $status->id,
                'is_currently_playing' => $status->slug === 'playing' ? 1 : 0,
                'updated_at' => now()
            ]);

        if ($updatedRows === 0) {
            return ['success' => false, 'message' => 'El juego no está en tu biblioteca.'];
        }

        return ['success' => true, 'message' => 'Estado del juego actualizado.', 'status' => $status->name];
    }

    /**
     * Update the user's personal game data in their library (rating, hours, notes, etc.).
     * The game MUST already be linked to the user's library.
     *
     * @param User $user
     * @param int $gameId
     * @param array $data
     * @return array
     */
    public function updateUserGame(User $user, int $gameId, array $data): array
    {
        $userGame = DB::table('user_games')
            ->where('user_id', $user->id)
            ->where('game_id', $gameId)
            ->first();

        if (!$userGame) {
            return ['success' => false, 'message' => 'Debes tener el juego vinculado a tu biblioteca para actualizarlo.'];
        }

        // If status_id is being updated, sync is_currently_playing automatically
        if (isset($data['game_status_id'])) {
            $newStatus = GameStatus::find($data['game_status_id']);
            $data['is_currently_playing'] = ($newStatus && $newStatus->slug === 'playing') ? 1 : 0;
        }

        DB::table('user_games')
            ->where('id', $userGame->id)
            ->update(array_merge(
                $data,
                ['updated_at' => now()]
            ));

        // Return updated entry with related models
        $updated = DB::table('user_games')->where('id', $userGame->id)->first();

        return ['success' => true, 'data' => $updated];
    }

    /**
     * Return aggregated community stats for a game in a single DB round-trip.
     * Rating scale is 0-100 (Metacritic-style).
     *
     * @param int $gameId
     * @return array{
     *   players_count: int,
     *   avg_rating: float|null,
     *   avg_hours: float|null,
     *   reviews_count: int,
     *   status_breakdown: array
     * }
     */
    public function getGameStats(int $gameId): array
    {
        // ── Global aggregates ──────────────────────────────────────────────
        $agg = DB::table('user_games')
            ->selectRaw("
                COUNT(*)                                              AS players_count,
                AVG(CASE WHEN rating > 0 THEN rating END)            AS avg_rating,
                AVG(CASE WHEN hours_played > 0 THEN hours_played END) AS avg_hours,
                COUNT(CASE WHEN notes IS NOT NULL AND notes != '' THEN 1 END) AS reviews_count
            ")
            ->where('game_id', $gameId)
            ->first();

        // ── Status breakdown ───────────────────────────────────────────────
        $breakdown = DB::table('user_games as ug')
            ->join('game_statuses as gs', 'gs.id', '=', 'ug.game_status_id')
            ->select('gs.id', 'gs.name', 'gs.slug', DB::raw('COUNT(*) as total'))
            ->where('ug.game_id', $gameId)
            ->whereNotNull('ug.game_status_id')
            ->groupBy('gs.id', 'gs.name', 'gs.slug')
            ->orderByDesc('total')
            ->get()
            ->map(fn($row) => [
                'id'    => $row->id,
                'name'  => $row->name,
                'slug'  => $row->slug,
                'total' => (int) $row->total,
            ])
            ->values()
            ->toArray();

        return [
            'players_count'    => (int) ($agg->players_count ?? 0),
            'avg_rating'       => $agg->avg_rating ? round((float) $agg->avg_rating, 1) : null,
            'avg_hours'        => $agg->avg_hours  ? round((float) $agg->avg_hours,  1) : null,
            'reviews_count'    => (int) ($agg->reviews_count ?? 0),
            'status_breakdown' => $breakdown,
        ];
    }

    /**
     * Paginated reviews (UserGame entries that have notes).
     * Eager-loads user and status to avoid N+1.
     *
     * @param int $gameId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getGameReviews(int $gameId, int $perPage = 5)
    {
        return UserGame::with(['user', 'status'])
            ->where('game_id', $gameId)
            ->whereNotNull('notes')
            ->where('notes', '!=', '')
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();
    }
}
