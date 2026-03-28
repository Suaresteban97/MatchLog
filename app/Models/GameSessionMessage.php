<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\GameSession;

class GameSessionMessage extends Model
{
    protected $fillable = ['session_id', 'user_id', 'message'];

    public function session()
    {
        return $this->belongsTo(GameSession::class, 'session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
