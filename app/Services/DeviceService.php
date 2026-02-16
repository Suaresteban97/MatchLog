<?php

namespace App\Services;

use App\Models\UserDevice;
use App\Models\DeviceCharacteristic;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DeviceService
{
    /**
     * Get all devices for a user
     */
    public function getUserDevices(User $user)
    {
        return UserDevice::with(['device', 'characteristics.pcComponent'])
            ->where('user_id', $user->id)
            ->get();
    }

    /**
     * Get a specific device for a user
     */
    public function getUserDevice(User $user, $deviceId)
    {
        return UserDevice::where('user_id', $user->id)
            ->where('id', $deviceId)
            ->with(['device', 'characteristics.pcComponent'])
            ->firstOrFail();
    }

    /**
     * Store a new device with characteristics
     */
    public function storeDevice(User $user, array $data)
    {
        DB::beginTransaction();

        try {
            $userDevice = new UserDevice();
            $userDevice->user_id = $user->id;
            $userDevice->device_id = $data['device_id'];
            $userDevice->custom_name = $data['custom_name'];
            $userDevice->save();

            if (isset($data['characteristics']) && is_array($data['characteristics'])) {
                foreach ($data['characteristics'] as $characteristic) {
                    $this->createCharacteristic($userDevice->id, $characteristic);
                }
            }

            DB::commit();

            return $userDevice->load(['device', 'characteristics.pcComponent']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing device and its characteristics
     */
    public function updateDevice(User $user, $deviceId, array $data)
    {
        $userDevice = UserDevice::where('id', $deviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$userDevice) {
            return null;
        }

        DB::beginTransaction();

        try {
            if (isset($data['custom_name'])) {
                $userDevice->custom_name = $data['custom_name'];
                $userDevice->save();
            }

            if (isset($data['characteristics']) && is_array($data['characteristics'])) {
                // Remove old characteristics
                DeviceCharacteristic::where('user_device_id', $userDevice->id)->delete();

                // Add new ones
                foreach ($data['characteristics'] as $characteristic) {
                    $this->createCharacteristic($userDevice->id, $characteristic);
                }
            }

            DB::commit();

            return $userDevice->load(['device', 'characteristics.pcComponent']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a device
     */
    public function deleteDevice(User $user, $deviceId)
    {
        $userDevice = UserDevice::where('id', $deviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$userDevice) {
            return false;
        }

        return $userDevice->delete();
    }

    /**
     * Helper to create a characteristic
     */
    protected function createCharacteristic($userDeviceId, array $data)
    {
        $deviceChar = new DeviceCharacteristic();
        $deviceChar->user_device_id = $userDeviceId;
        $deviceChar->key = $data['key'];
        $deviceChar->value = $data['value'] ?? null;
        $deviceChar->pc_component_id = $data['pc_component_id'] ?? null;
        $deviceChar->save();
        return $deviceChar;
    }
}
