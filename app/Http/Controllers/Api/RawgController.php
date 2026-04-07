<?php

namespace App\Http\Controllers\Api;

use App\Services\Api\RawgService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Game;

/**
 * RawgController
 *
 * Admin-only controller that exposes endpoints to populate (seed)
 * the local game catalog by syncing data from the RAWG API.
 *
 * Endpoints:
 *  POST /api/admin/rawg/sync-list          - Syncs a page of games from the RAWG list
 *  POST /api/admin/rawg/sync-detail/{slug} - Enriches one game by slug
 */
class RawgController extends Controller
{
    public function __construct(protected RawgService $rawg) {}

    /**
     * Sync a page of games from RAWG into the local database.
     *
     * Query params:
     *   page     (int, default: 1)
     *   per_page (int, default: 40, max: 40)
     *
     * POST /api/admin/rawg/sync-list
     */
    public function syncList(Request $request)
    {
        $page    = (int) $request->input('page', 1);
        $perPage = min((int) $request->input('per_page', 40), 40);

        $data = $this->rawg->fetchGames($page, $perPage);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch data from RAWG. Check the API key and logs.',
            ], 502);
        }

        $count = 0;
        foreach ($data['results'] as $rawgGame) {
            try {
                $this->rawg->upsertGameFromList($rawgGame);
                $count++;
            } catch (\Exception $e) {
                Log::warning('RAWG upsert failed for game', [
                    'name'  => $rawgGame['name'] ?? '?',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success'      => true,
            'page'         => $page,
            'synced'       => $count,
            'rawg_total'   => $data['count'] ?? null,
            'rawg_next'    => $data['next'] ?? null,
        ]);
    }

    /**
     * Enrich one game in the DB with full RAWG detail data.
     *
     * POST /api/admin/rawg/sync-detail/{slug}
     * where {slug} is the game's RAWG slug (e.g. "halo-infinite")
     */
    public function syncDetail(Request $request, string $slug)
    {
        // Find local game by slug (set during upsertGameFromList)
        $game = Game::where('slug', $slug)->first();

        if (!$game) {
            return response()->json([
                'success' => false,
                'message' => "Game with slug '{$slug}' not found in local DB. Run sync-list first.",
            ], 404);
        }

        $detail = $this->rawg->fetchGameDetail($slug);

        if (!$detail) {
            return response()->json([
                'success' => false,
                'message' => "RAWG returned no data for slug '{$slug}'.",
            ], 502);
        }

        $this->rawg->enrichGameWithDetail($game, $detail);

        return response()->json([
            'success' => true,
            'game'    => [
                'id'          => $game->id,
                'name'        => $game->name,
                'description' => substr($game->fresh()->description ?? '', 0, 150) . '...',
            ],
        ]);
    }
}
