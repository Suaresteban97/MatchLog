<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = ['name'];

    public function userDevices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_devices');
    }

}
