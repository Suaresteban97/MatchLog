<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceCharacteristic extends Model
{
    public function userDevice()
    {
        return $this->belongsTo(UserDevice::class);
    }
}
