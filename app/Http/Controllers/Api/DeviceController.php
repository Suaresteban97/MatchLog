<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Device\AddDeviceRequest;
use App\Http\Requests\Device\UpdateDeviceRequest;
use App\Models\UserDevice;
use App\Models\DeviceCharacteristic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceController extends Controller
{
    /**
     * Get all devices for the authenticated user
     */
    public function index(Request $request)
    {
        $devices = UserDevice::with(['device', 'characteristics.pcComponent'])
            ->where('user_id', $request->user()->id)
            ->get();

        return response()->json([
            'devices' => $devices
        ], 200);
    }

    /**
     * Add a new device to the user
     */
    public function store(AddDeviceRequest $request)
    {
        DB::beginTransaction();

        try {
            $userDevice = new UserDevice();
            $userDevice->user_id = $request->user()->id;
            $userDevice->device_id = $request->device_id;
            $userDevice->custom_name = $request->custom_name;
            $userDevice->save();

            if ($request->has('characteristics')) {
                foreach ($request->characteristics as $characteristic) {
                    $deviceChar = new DeviceCharacteristic();
                    $deviceChar->user_device_id = $userDevice->id;
                    $deviceChar->key = $characteristic['key'];
                    $deviceChar->value = $characteristic['value'] ?? null;
                    $deviceChar->pc_component_id = $characteristic['pc_component_id'] ?? null;
                    $deviceChar->save();
                }
            }

            DB::commit();

            $userDevice->load(['device', 'characteristics.pcComponent']);

            return response()->json([
                'message' => 'Dispositivo agregado correctamente',
                'device' => $userDevice
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Error al agregar dispositivo',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a user's device
     */
    public function update(UpdateDeviceRequest $request, $id)
    {
        $userDevice = UserDevice::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$userDevice) {
            return response()->json([
                'message' => 'Dispositivo no encontrado'
            ], 404);
        }

        DB::beginTransaction();

        try {
            if ($request->has('custom_name')) {
                $userDevice->custom_name = $request->custom_name;
                $userDevice->save();
            }

            if ($request->has('characteristics')) {
                DeviceCharacteristic::where('user_device_id', $userDevice->id)->delete();

                foreach ($request->characteristics as $characteristic) {
                    $deviceChar = new DeviceCharacteristic();
                    $deviceChar->user_device_id = $userDevice->id;
                    $deviceChar->key = $characteristic['key'];
                    $deviceChar->value = $characteristic['value'] ?? null;
                    $deviceChar->pc_component_id = $characteristic['pc_component_id'] ?? null;
                    $deviceChar->save();
                }
            }

            DB::commit();

            $userDevice->load(['device', 'characteristics.pcComponent']);

            return response()->json([
                'message' => 'Dispositivo actualizado correctamente',
                'device' => $userDevice
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Error al actualizar dispositivo',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a user's device
     */
    public function destroy(Request $request, $id)
    {
        $userDevice = UserDevice::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$userDevice) {
            return response()->json([
                'message' => 'Dispositivo no encontrado'
            ], 404);
        }

        $userDevice->delete();

        return response()->json([
            'message' => 'Dispositivo eliminado correctamente'
        ], 200);
    }
}
