<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Device;
use App\Models\UserDevice;
use App\Models\PcComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DeviceControllerTest extends TestCase
{
    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user with token
        $this->user = User::factory()->create();
        $plainToken = Str::random(80);
        $this->user->api_token = hash('sha256', $plainToken);
        $this->user->token_expires_at = Carbon::now()->addDays(7);
        $this->user->save();

        $this->token = $plainToken;
    }

    /** @test */
    public function it_can_list_user_devices()
    {
        // Create a device for the user
        $device = Device::factory()->create();
        UserDevice::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $device->id,
            'custom_name' => 'Mi PC Gaming'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/devices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'devices' => [
                    '*' => ['id', 'user_id', 'device_id', 'custom_name']
                ]
            ]);
    }

    /** @test */
    public function it_can_add_a_device()
    {
        $device = Device::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/devices', [
            'device_id' => $device->id,
            'custom_name' => 'Mi Laptop'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Dispositivo agregado correctamente'
            ]);

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $this->user->id,
            'device_id' => $device->id,
            'custom_name' => 'Mi Laptop'
        ]);
    }

    /** @test */
    public function it_can_add_device_with_characteristics()
    {
        $device = Device::factory()->create();
        $cpu = PcComponent::factory()->create(['type' => 'cpu']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/devices', [
            'device_id' => $device->id,
            'custom_name' => 'Mi PC',
            'characteristics' => [
                [
                    'key' => 'cpu',
                    'pc_component_id' => $cpu->id
                ],
                [
                    'key' => 'ram',
                    'value' => '32GB DDR5'
                ]
            ]
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('device_characteristics', [
            'key' => 'cpu',
            'pc_component_id' => $cpu->id
        ]);

        $this->assertDatabaseHas('device_characteristics', [
            'key' => 'ram',
            'value' => '32GB DDR5'
        ]);
    }

    /** @test */
    public function it_validates_device_id_is_required()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/devices', [
            'custom_name' => 'Mi PC'
        ]);

        $response->assertStatus(400)
            ->assertJsonValidationErrors(['device_id']);
    }

    /** @test */
    public function it_can_update_device_custom_name()
    {
        $device = Device::factory()->create();
        $userDevice = UserDevice::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $device->id,
            'custom_name' => 'Old Name'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/devices/{$userDevice->id}", [
            'custom_name' => 'New Name'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Dispositivo actualizado correctamente'
            ]);

        $this->assertDatabaseHas('user_devices', [
            'id' => $userDevice->id,
            'custom_name' => 'New Name'
        ]);
    }

    /** @test */
    public function it_can_delete_a_device()
    {
        $device = Device::factory()->create();
        $userDevice = UserDevice::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $device->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/devices/{$userDevice->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Dispositivo eliminado correctamente'
            ]);

        $this->assertDatabaseMissing('user_devices', [
            'id' => $userDevice->id
        ]);
    }

    /** @test */
    public function it_cannot_update_another_users_device()
    {
        $otherUser = User::factory()->create();
        $device = Device::factory()->create();
        $userDevice = UserDevice::factory()->create([
            'user_id' => $otherUser->id,
            'device_id' => $device->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/devices/{$userDevice->id}", [
            'custom_name' => 'Hacked'
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/devices');

        $response->assertStatus(403);
    }
}
