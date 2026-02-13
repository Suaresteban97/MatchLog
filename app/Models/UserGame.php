<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGame extends Model
{
    protected $fillable = [
        'user_id',
        'game_id',
        'game_status_id',
        'game_platform_id',
        'hours_played',
        'is_currently_playing',
        'started_at',
        'completed_at',
        'rating',
        'notes',
    ];

    protected $casts = [
        'hours_played' => 'decimal:2',
        'is_currently_playing' => 'boolean',
        'started_at' => 'date',
        'completed_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(GameStatus::class, 'game_status_id');
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(GamePlatform::class, 'game_platform_id');
    }
}
