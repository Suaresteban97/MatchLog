<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class GamePlatform extends Model
{
    protected $fillable = [
        'name',
    ];

    /**
     * Games available on this platform.
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_game_platform')
            ->withPivot('release_date');
    }

    /**
     * Community-proposed contributions targeting this platform.
     */
    public function contributions(): MorphMany
    {
        return $this->morphMany(Contribution::class, 'contributable');
    }
}
