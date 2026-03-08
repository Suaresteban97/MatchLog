<?php

namespace App\Http\Resources\Games;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserGameResource extends JsonResource
{
    /**
     * Transform the user game into an array that looks like a Game with user_game progress.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->game->id,
            'name' => $this->game->name,
            'cover_image_url' => $this->game->cover_image_url,
            'metacritic_score' => $this->game->metacritic_score,

            // User progress
            'user_game' => [
                'status' => $this->status ? [
                    'id' => $this->status->id,
                    'name' => $this->status->name,
                    'slug' => $this->status->slug,
                ] : null,
                'platform' => $this->platform ? [
                    'id' => $this->platform->id,
                    'name' => $this->platform->name,
                ] : null,
                'hours_played' => $this->hours_played,
                'rating' => $this->rating,
                'is_currently_playing' => $this->is_currently_playing,
                'started_at' => $this->started_at?->format('Y-m-d'),
                'completed_at' => $this->completed_at?->format('Y-m-d'),
                'notes' => $this->notes,
            ],
        ];
    }
}
