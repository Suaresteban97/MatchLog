<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Device;

class DeviceController extends Controller
{
    public function store(Request $request) {
        $user = $request->user();

        $deviceIds = $request->input('device_ids', []);

        $validDeviceIds = Device::whereIn('id', $deviceIds)->pluck('id')->toArray();

        if (count($validDeviceIds) !== count($deviceIds)) {
            return response()->json([
                'message' => 'Uno o más dispositivos no existen.'
            ], 422);
        }

        $user->devices()->syncWithoutDetaching($validDeviceIds);

        return response()->json([
            'code' => 201,
            'message' => 'Dispositivos agregados correctamente.'
        ], 201);
    }
}
