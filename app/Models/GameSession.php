<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    protected $fillable = [
        'host_id',
        'title',
        'game_id',
        'description',
        'start_time',
        'max_participants',
        'status',
        'link'
    ];

    protected $casts = [
        'start_time' => 'datetime',
    ];

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'game_session_participants')
            ->withPivot('status')
            ->withTimestamps();
    }
}
