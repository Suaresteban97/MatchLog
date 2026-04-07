<?php

namespace App\Services\Api;

use App\Models\Game;
use App\Models\Genre;
use App\Models\GamePlatform;
use App\Models\GameScreenshot;
use Illuminate\Support\Facades\Log;

class RawgService extends ApiService
{
    protected string $baseUrl = 'https://api.rawg.io/api/';

    public function __construct()
    {
        $this->defaultParams = [
            'key' => config('services.rawg.key'),
        ];
    }

    /**
     * Fetch a paginated list of games from RAWG.
     */
    public function fetchGames(int $page = 1, int $perPage = 40): ?array
    {
        $response = $this->get('games', [
            'page'      => $page,
            'page_size' => $perPage,
            'ordering'  => '-rating',
        ]);

        if (!$response->successful()) {
            Log::error('RAWG fetchGames failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        }

        return $response->json();
    }

    /**
     * Fetch the detailed data for a single game by its RAWG slug.
     */
    public function fetchGameDetail(string $slug): ?array
    {
        $response = $this->get("games/{$slug}");

        if (!$response->successful()) {
            Log::warning('RAWG fetchGameDetail failed', [
                'slug'   => $slug,
                'status' => $response->status(),
            ]);
            return null;
        }

        return $response->json();
    }

    /**
     * Upsert a game from RAWG list payload.
     * Also syncs genres, platforms and short_screenshots.
     */
    public function upsertGameFromList(array $rawgGame): Game
    {
        $game = Game::updateOrCreate(
            ['igdb_id' => $rawgGame['id']],
            [
                'name'             => $rawgGame['name'],
                'cover_image_url'  => $rawgGame['background_image'] ?? null,
                'release_date'     => $rawgGame['released'] ?? null,
                'metacritic_score' => $rawgGame['metacritic'] ?? null,
                'is_multiplayer'   => false, // Enriched later via detail
            ]
        );

        // Sync genres
        if (!empty($rawgGame['genres'])) {
            $genreIds = [];
            foreach ($rawgGame['genres'] as $g) {
                $genre = Genre::firstOrCreate(
                    ['slug' => $g['slug']],
                    ['name' => $g['name']]
                );
                $genreIds[] = $genre->id;
            }
            $game->genres()->sync($genreIds);
        }

        // Sync platforms
        if (!empty($rawgGame['platforms'])) {
            $platformIds = [];
            foreach ($rawgGame['platforms'] as $p) {
                $pd = $p['platform'];
                $platform = GamePlatform::firstOrCreate(['name' => $pd['name']]);
                $platformIds[] = $platform->id;
            }
            $game->platforms()->sync($platformIds);
        }

        // Save short_screenshots (cover at rawg_id=-1 + gallery)
        if (!empty($rawgGame['short_screenshots'])) {
            foreach ($rawgGame['short_screenshots'] as $shot) {
                $rawgId   = $shot['id'];   // -1 means the background cover
                $isCover  = ($rawgId === -1);
                GameScreenshot::updateOrCreate(
                    ['game_id' => $game->id, 'rawg_id' => $rawgId],
                    ['image_url' => $shot['image'], 'is_cover' => $isCover]
                );
            }
        }

        return $game;
    }

    /**
     * Enrich an existing game with full detail from RAWG game detail endpoint.
     */
    public function enrichGameWithDetail(Game $game, array $detail): Game
    {
        $game->update([
            'description'           => strip_tags($detail['description'] ?? ''),
            'developer'             => $this->extractFirstCompany($detail['developers'] ?? []),
            'publisher'             => $this->extractFirstCompany($detail['publishers'] ?? []),
            'metacritic_score'      => $detail['metacritic'] ?? $game->metacritic_score,
            'cover_image_url'       => $detail['background_image'] ?? $game->cover_image_url,
            'is_multiplayer'        => $this->detectMultiplayer($detail),
            'is_cooperative'        => $this->detectCooperative($detail),
            'is_online_multiplayer' => $this->detectOnlineMultiplayer($detail),
            'is_local_multiplayer'  => $this->detectLocalMultiplayer($detail),
        ]);

        // Sync platforms again from detail (can have more than list)
        if (!empty($detail['platforms'])) {
            $platformIds = [];
            foreach ($detail['platforms'] as $p) {
                $pd = $p['platform'];
                $platform = GamePlatform::firstOrCreate(['name' => $pd['name']]);
                $platformIds[] = $platform->id;
            }
            $game->platforms()->sync($platformIds);
        }

        return $game;
    }

    // =========================================================
    // Private helpers
    // =========================================================

    private function extractFirstCompany(array $items): ?string
    {
        return $items[0]['name'] ?? null;
    }

    private function hasTags(array $detail, array $keywords): bool
    {
        $tags     = array_merge($detail['tags'] ?? [], $detail['genres'] ?? []);
        $haystack = strtolower(implode(' ', array_column($tags, 'name')));
        foreach ($keywords as $kw) {
            if (str_contains($haystack, strtolower($kw))) return true;
        }
        return false;
    }

    private function detectMultiplayer(array $d): bool
    {
        return $this->hasTags($d, ['multiplayer', 'multi-player', 'online pvp', 'co-op', 'cooperative']);
    }

    private function detectCooperative(array $d): bool
    {
        return $this->hasTags($d, ['co-op', 'cooperative', 'co op']);
    }

    private function detectOnlineMultiplayer(array $d): bool
    {
        return $this->hasTags($d, ['online pvp', 'online multiplayer', 'online co-op']);
    }

    private function detectLocalMultiplayer(array $d): bool
    {
        return $this->hasTags($d, ['local multiplayer', 'local co-op', 'split screen', 'split-screen', 'couch co-op']);
    }
}
