<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Device extends Model
{
    use HasFactory;

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
