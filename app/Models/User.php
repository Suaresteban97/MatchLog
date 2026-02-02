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

    public function userDevices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function devices()
    {
        return $this->belongsToMany(Device::class, 'user_devices');
    }

    // public function googleToken() {
    //     return $this->belongsToOne(GoogleToken::class, 'user_id')->where("provider", "google");
    // }

    public function userInfo()
    {
        return $this->belongsToOne(InfoUser::class, 'user_id');
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
