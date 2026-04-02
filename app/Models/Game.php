<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Game extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'cover_image_url',
        'release_date',
        'developer',
        'publisher',
        'metacritic_score',
        'igdb_id',
        'is_multiplayer',
        'is_online_multiplayer',
        'is_local_multiplayer',
        'is_cooperative',
        'max_players',
    ];

    protected $casts = [
        'release_date' => 'date',
        'is_multiplayer' => 'boolean',
        'is_online_multiplayer' => 'boolean',
        'is_local_multiplayer' => 'boolean',
        'is_cooperative' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($game) {
            if (empty($game->slug)) {
                $baseSlug = Str::slug($game->name);
                $slug = $baseSlug;
                $count = 1;
                while (static::where('slug', $slug)->where('id', '!=', $game->id)->exists()) {
                    $slug = $baseSlug . '-' . $count++;
                }
                $game->slug = $slug;
            }
        });
    }

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

    /**
     * User-game pivot records for this game (including status).
     */
    public function userGames(): HasMany
    {
        return $this->hasMany(UserGame::class);
    }

    /**
     * Screenshots stored from RAWG sync.
     */
    public function screenshots(): HasMany
    {
        return $this->hasMany(GameScreenshot::class);
    }
}
