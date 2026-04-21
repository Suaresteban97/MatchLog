<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    //Relations

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function isAdmin(): bool
    {
        return $this->role !== null && $this->role->slug === 'admin';
    }

    public function userDevices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function devices()
    {
        return $this->belongsToMany(Device::class, 'user_devices');
    }

    // Collections
    public function collections()
    {
        return $this->hasMany(Collection::class);
    }

    // public function googleToken() {
    //     return $this->belongsToOne(GoogleToken::class, 'user_id')->where("provider", "google");
    // }

    public function userInfo()
    {
        return $this->belongsTo(InfoUser::class, 'user_id');
    }

    // Social Profiles
    public function socialProfiles()
    {
        return $this->hasMany(UserSocialProfile::class);
    }

    // Execution Platforms
    public function executionPlatforms()
    {
        return $this->belongsToMany(ExecutionPlatform::class, 'user_execution_platforms')
            ->withPivot('account_identifier', 'created_at')
            ->withTimestamps();
    }

    // Followers System
    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    // Friendships System
    public function friendRequestsSent()
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    public function friendRequestsReceived()
    {
        return $this->hasMany(Friendship::class, 'friend_id');
    }

    /**
     * Helper to get all accepted friends (merges both sides of the relationship)
     */
    public function getFriendsAttribute()
    {
        $friendsSent = $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 'accepted')
            ->withTimestamps()
            ->get();

        $friendsReceived = $this->belongsToMany(User::class, 'friendships', 'friend_id', 'user_id')
            ->wherePivot('status', 'accepted')
            ->withTimestamps()
            ->get();

        return $friendsSent->merge($friendsReceived);
    }

    // Direct Messages & Conversations
    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'user1_id')
            ->union($this->hasMany(Conversation::class, 'user2_id')->toBase());
    }

    public function sentDirectMessages()
    {
        return $this->hasMany(DirectMessage::class, 'sender_id');
    }

    // Game Sessions
    public function sessionsHosting()
    {
        return $this->hasMany(GameSession::class, 'host_id');
    }

    public function sessionsParticipating()
    {
        return $this->belongsToMany(GameSession::class, 'game_session_participants')
            ->withPivot('status')
            ->withTimestamps();
    }

    // User's Game Library
    public function games()
    {
        return $this->belongsToMany(Game::class, 'user_games')
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

    public function simpleTransformer()
    {
        return [
            "code" => $this->id,
            "name" => $this->name,
            "email" => $this->email
        ];
    }
}
