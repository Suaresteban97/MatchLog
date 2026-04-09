<?php

namespace App\Http\Resources\Games;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameReviewResource extends JsonResource
{
    /**
     * Transform a UserGame (with notes) into a review card.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'user'        => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'status'      => $this->whenLoaded('status', fn () => $this->status ? [
                'id'   => $this->status->id,
                'name' => $this->status->name,
                'slug' => $this->status->slug,
            ] : null),
            'rating'      => $this->rating,       // 0-100 scale
            'notes'       => $this->notes,
            'hours_played'=> $this->hours_played ? (float) $this->hours_played : null,
            'updated_at'  => $this->updated_at?->toDateTimeString(),
        ];
    }
}
