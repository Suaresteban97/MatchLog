<?php

namespace App\Http\Resources\Games;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameDetailResource extends JsonResource
{
    /**
     * Transform the game into a detailed array.
     * Used for the show() endpoint.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Find this user's game record if it exists
        $authUser = $request->user();
        $userGame = $authUser
            ? $this->userGames->firstWhere('user_id', $authUser->id)
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'cover_image_url' => $this->cover_image_url,
            'release_date' => $this->release_date?->format('Y-m-d'),
            'developer' => $this->developer,
            'publisher' => $this->publisher,
            'metacritic_score' => $this->metacritic_score,
            'igdb_id' => $this->igdb_id,
            'is_multiplayer' => $this->is_multiplayer,
            'is_online_multiplayer' => $this->is_online_multiplayer,
            'is_local_multiplayer' => $this->is_local_multiplayer,
            'is_cooperative' => $this->is_cooperative,
            'max_players' => $this->max_players,

            // Clean genre list — no pivot objects
            'genres' => $this->whenLoaded(
                'genres',
                fn() =>
                $this->genres->map(fn($g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'slug' => $g->slug,
                ])
            ),

            // Clean platform list — no pivot objects
            'platforms' => $this->whenLoaded(
                'platforms',
                fn() =>
                $this->platforms->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug ?? null,
                ])
            ),

            // User's library entry for this game (null if not in their library)
            'user_game' => $userGame ? [
                'status' => $userGame->status ? [
                    'id' => $userGame->status->id,
                    'name' => $userGame->status->name,
                    'slug' => $userGame->status->slug,
                ] : null,
                'platform' => $userGame->platform ? [
                    'id' => $userGame->platform->id,
                    'name' => $userGame->platform->name,
                ] : null,
                'hours_played' => $userGame->hours_played,
                'rating' => $userGame->rating,
                'is_currently_playing' => $userGame->is_currently_playing,
                'started_at' => $userGame->started_at?->format('Y-m-d'),
                'completed_at' => $userGame->completed_at?->format('Y-m-d'),
                'notes' => $userGame->notes,
            ] : null,

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
