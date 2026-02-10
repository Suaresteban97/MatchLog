<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSocialProfile extends Model
{
    protected $fillable = [
        'user_id',
        'social_platform_id',
        'gamertag',
        'external_user_id',
        'profile_url'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function socialPlatform()
    {
        return $this->belongsTo(SocialPlatform::class);
    }
}
