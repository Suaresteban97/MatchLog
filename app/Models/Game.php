<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Game extends Model
{
    protected $fillable = [
        'name',
        'description',
        'cover_image_url',
        'release_date',
        'developer',
        'publisher',
        'metacritic_score',
        'igdb_id',
    ];

    protected $casts = [
        'release_date' => 'date',
    ];

    /**
     * Get the game sessions for this game.
     */
    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    /**
     * Genres that belong to this game.
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'game_genre');
    }

    /**
     * Platforms where this game is available.
     */
    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(GamePlatform::class, 'game_game_platform')
            ->withPivot('release_date');
    }

    /**
     * Users who have this game in their library.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_games')
            ->withPivot(
                'game_status_id',
                'game_platform_id',
                'hours_played',
                'is_currently_playing',
                'started_at',
                'completed_at',
                'rating',
                'notes'
            )
            ->withTimestamps();
    }
}
