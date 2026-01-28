<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeviceCharacteristic extends Model
{
    use HasFactory;

    public function userDevice()
    {
        return $this->belongsTo(UserDevice::class);
    }

    public function pcComponent()
    {
        return $this->belongsTo(PcComponent::class);
    }
}
