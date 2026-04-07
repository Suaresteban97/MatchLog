<?php

namespace App\Http\Resources\Games;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'cover_image_url' => $this->cover_image_url,
            'metacritic_score' => $this->metacritic_score,
            'genres' => $this->whenLoaded('genres', function () {
                return $this->genres->pluck('name');
            }, []),
        ];
    }
}
