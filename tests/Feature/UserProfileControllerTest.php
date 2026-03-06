<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\InfoUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserProfileControllerTest extends TestCase
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
    public function it_can_get_user_profile()
    {
        InfoUser::factory()->create([
            'user_id' => $this->user->id,
            'nickname' => 'TestGamer',
            'bio' => 'I love gaming'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'profile' => ['id', 'user_id', 'nickname', 'bio']
            ])
            ->assertJson([
                'profile' => [
                    'nickname' => 'TestGamer',
                    'bio' => 'I love gaming'
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_if_profile_not_found()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/profile');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Perfil no encontrado'
            ]);
    }

    /** @test */
    public function it_can_update_profile()
    {
        InfoUser::factory()->create([
            'user_id' => $this->user->id,
            'nickname' => 'OldNick'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/profile', [
            'nickname' => 'NewNick',
            'bio' => 'Updated bio',
            'share_email' => true,
            'available_for_online' => false
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Perfil actualizado correctamente'
            ]);

        $this->assertDatabaseHas('info_users', [
            'user_id' => $this->user->id,
            'nickname' => 'NewNick',
            'bio' => 'Updated bio',
            'share_email' => true,
            'available_for_online' => false
        ]);
    }

    /** @test */
    public function it_creates_profile_if_not_exists()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/profile', [
            'nickname' => 'NewGamer',
            'bio' => 'First time here'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('info_users', [
            'user_id' => $this->user->id,
            'nickname' => 'NewGamer',
            'bio' => 'First time here'
        ]);
    }

    /** @test */
    public function it_validates_bio_max_length()
    {
        InfoUser::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/profile', [
            'bio' => str_repeat('a', 1001) // Exceeds 1000 chars
        ]);

        $response->assertStatus(400)
            ->assertJsonValidationErrors(['bio']);
    }

    /** @test */
    public function it_validates_nickname_max_length()
    {
        InfoUser::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/profile', [
            'nickname' => str_repeat('a', 101) // Exceeds 100 chars
        ]);

        $response->assertStatus(400)
            ->assertJsonValidationErrors(['nickname']);
    }

    /** @test */
    public function it_validates_age_is_integer()
    {
        InfoUser::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/profile', [
            'age' => 'not a number'
        ]);

        $response->assertStatus(400)
            ->assertJsonValidationErrors(['age']);
    }

    /** @test */
    public function it_validates_share_email_is_boolean()
    {
        InfoUser::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/profile', [
            'share_email' => 'yes'
        ]);

        $response->assertStatus(400)
            ->assertJsonValidationErrors(['share_email']);
    }

    /** @test */
    public function it_can_update_partial_fields()
    {
        InfoUser::factory()->create([
            'user_id' => $this->user->id,
            'nickname' => 'OldNick',
            'bio' => 'Old bio'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/profile', [
            'nickname' => 'NewNick'
            // bio not provided, should remain unchanged
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('info_users', [
            'user_id' => $this->user->id,
            'nickname' => 'NewNick',
            'bio' => 'Old bio' // Unchanged
        ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/profile');

        $response->assertStatus(403);
    }
}
