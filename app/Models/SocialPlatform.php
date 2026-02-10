<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialPlatform extends Model
{
    protected $fillable = ['name', 'icon_url'];

    public function userSocialProfiles()
    {
        return $this->hasMany(UserSocialProfile::class);
    }
}
