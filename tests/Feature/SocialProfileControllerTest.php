<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SocialPlatform;
use App\Models\UserSocialProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SocialProfileControllerTest extends TestCase
{
    protected $user;
    protected $platform;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Generate valid API token
        $this->token = bin2hex(random_bytes(32));
        $this->user->api_token = hash('sha256', $this->token);
        $this->user->token_expires_at = now()->addDays(7);
        $this->user->save();

        $this->platform = SocialPlatform::create([
            'name' => 'Steam',
            'icon_url' => 'steam.png'
        ]);
    }

    protected function headers()
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    public function test_user_can_list_their_social_profiles()
    {
        UserSocialProfile::create([
            'user_id' => $this->user->id,
            'social_platform_id' => $this->platform->id,
            'gamertag' => 'TestGamer123'
        ]);

        $response = $this->withHeaders($this->headers())->getJson('/api/social-profiles');

        $response->assertStatus(200)
            ->assertJsonStructure(['profiles'])
            ->assertJsonCount(1, 'profiles');
    }

    public function test_user_can_create_social_profile()
    {
        $profileData = [
            'social_platform_id' => $this->platform->id,
            'gamertag' => 'NewGamer456',
            'profile_url' => 'https://steamcommunity.com/id/newgamer456'
        ];

        $response = $this->withHeaders($this->headers())->postJson('/api/social-profiles', $profileData);

        $response->assertStatus(201)
            ->assertJsonStructure(['profile']);

        $this->assertDatabaseHas('user_social_profiles', [
            'user_id' => $this->user->id,
            'gamertag' => 'NewGamer456'
        ]);
    }

    public function test_user_can_update_social_profile()
    {
        $profile = UserSocialProfile::create([
            'user_id' => $this->user->id,
            'social_platform_id' => $this->platform->id,
            'gamertag' => 'OldName'
        ]);

        $response = $this->withHeaders($this->headers())->putJson("/api/social-profiles/{$profile->id}", [
            'gamertag' => 'UpdatedName'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_social_profiles', [
            'id' => $profile->id,
            'gamertag' => 'UpdatedName'
        ]);
    }

    public function test_user_can_delete_social_profile()
    {
        $profile = UserSocialProfile::create([
            'user_id' => $this->user->id,
            'social_platform_id' => $this->platform->id,
            'gamertag' => 'ToDelete'
        ]);

        $response = $this->withHeaders($this->headers())->deleteJson("/api/social-profiles/{$profile->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('user_social_profiles', ['id' => $profile->id]);
    }

    public function test_can_get_available_platforms()
    {
        $response = $this->withHeaders($this->headers())->getJson('/api/social-platforms');

        $response->assertStatus(200)
            ->assertJsonStructure(['platforms']);
    }

    public function test_validation_fails_without_required_fields()
    {
        $response = $this->withHeaders($this->headers())->postJson('/api/social-profiles', []);

        $response->assertStatus(400)
            ->assertJsonValidationErrors(['social_platform_id', 'gamertag']);
    }
}
