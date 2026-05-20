<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InfoUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'age',
        'genre',
        'nickname',
        'bio',
        'photo',
        'share_email',
        'available_for_online',
        'show_posts',
        'show_backlog',
        'show_collections',
        'show_groups',
        'show_social_profiles',
        'show_devices',
    ];

    protected $casts = [
        'share_email'         => 'boolean',
        'available_for_online'=> 'boolean',
        'show_posts'          => 'boolean',
        'show_backlog'        => 'boolean',
        'show_collections'    => 'boolean',
        'show_groups'         => 'boolean',
        'show_social_profiles'=> 'boolean',
        'show_devices'        => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
