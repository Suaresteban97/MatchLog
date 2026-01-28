<?php

namespace Database\Factories;

use App\Models\UserDevice;
use App\Models\User;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserDeviceFactory extends Factory
{
    protected $model = UserDevice::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_id' => Device::factory(),
            'custom_name' => $this->faker->optional()->words(3, true),
        ];
    }
}
