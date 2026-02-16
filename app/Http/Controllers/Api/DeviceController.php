<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Device\AddDeviceRequest;
use App\Http\Requests\Device\UpdateDeviceRequest;
use App\Services\DeviceService;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    protected $deviceService;

    public function __construct(DeviceService $deviceService)
    {
        $this->deviceService = $deviceService;
    }

    /**
     * Get all devices for the authenticated user
     */
    public function index(Request $request)
    {
        $devices = $this->deviceService->getUserDevices($request->user());

        return response()->json([
            'devices' => $devices
        ], 200);
    }

    /**
     * Show single device
     */
    public function show(Request $request, $id)
    {
        try {
            $userDevice = $this->deviceService->getUserDevice($request->user(), $id);
            return response()->json(['device' => $userDevice], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Dispositivo no encontrado'], 404);
        }
    }

    /**
     * Add a new device to the user
     */
    public function store(AddDeviceRequest $request)
    {
        try {
            $userDevice = $this->deviceService->storeDevice($request->user(), $request->validated());

            return response()->json([
                'message' => 'Dispositivo agregado correctamente',
                'device' => $userDevice
            ], 201);
        } catch (\Exception $e) {
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
        try {
            $userDevice = $this->deviceService->updateDevice($request->user(), $id, $request->validated());

            if (!$userDevice) {
                return response()->json([
                    'message' => 'Dispositivo no encontrado'
                ], 404);
            }

            return response()->json([
                'message' => 'Dispositivo actualizado correctamente',
                'device' => $userDevice
            ], 200);
        } catch (\Exception $e) {
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
        $deleted = $this->deviceService->deleteDevice($request->user(), $id);

        if (!$deleted) {
            return response()->json([
                'message' => 'Dispositivo no encontrado'
            ], 404);
        }

        return response()->json([
            'message' => 'Dispositivo eliminado correctamente'
        ], 200);
    }
}
