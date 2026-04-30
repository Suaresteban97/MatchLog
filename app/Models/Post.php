<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'game_id',
        'collection_id',
        'game_session_id',
        'user_device_id',
        'share_social_profile',
    ];

    protected $casts = [
        'share_social_profile' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function gameSession()
    {
        return $this->belongsTo(GameSession::class);
    }

    public function userDevice()
    {
        return $this->belongsTo(UserDevice::class);
    }

    public function comments()
    {
        return $this->hasMany(PostComment::class)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->latest();
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class);
    }

    public function isLikedBy(User $user): bool
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }
}
