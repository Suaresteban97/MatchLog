<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Genre extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Games in this genre.
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_genre');
    }

    /**
     * Community-proposed contributions targeting this genre.
     */
    public function contributions(): MorphMany
    {
        return $this->morphMany(Contribution::class, 'contributable');
    }
}
