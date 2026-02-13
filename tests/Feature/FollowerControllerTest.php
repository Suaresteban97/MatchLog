<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FollowerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user1;
    protected $user2;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::factory()->create(['name' => 'User One']);
        $this->user2 = User::factory()->create(['name' => 'User Two']);

        // Generate valid API token for user1
        $this->token = bin2hex(random_bytes(32));
        $this->user1->api_token = hash('sha256', $this->token);
        $this->user1->token_expires_at = now()->addDays(7);
        $this->user1->save();
    }

    protected function headers()
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    public function test_user_can_follow_another_user()
    {
        $response = $this->withHeaders($this->headers())->postJson('/api/follow', [
            'user_id' => $this->user2->id
        ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Ahora sigues a este usuario']);

        $this->assertDatabaseHas('followers', [
            'follower_id' => $this->user1->id,
            'following_id' => $this->user2->id
        ]);
    }

    public function test_user_cannot_follow_themselves()
    {
        $response = $this->withHeaders($this->headers())->postJson('/api/follow', [
            'user_id' => $this->user1->id
        ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'No puedes seguirte a ti mismo']);
    }

    public function test_user_can_unfollow_another_user()
    {
        $this->user1->following()->attach($this->user2->id);

        $response = $this->withHeaders($this->headers())->postJson('/api/unfollow', [
            'user_id' => $this->user2->id
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('followers', [
            'follower_id' => $this->user1->id,
            'following_id' => $this->user2->id
        ]);
    }

    public function test_user_can_get_followers_list()
    {
        $this->user2->following()->attach($this->user1->id);

        $response = $this->withHeaders($this->headers())->getJson('/api/followers');

        $response->assertStatus(200)
            ->assertJsonStructure(['followers'])
            ->assertJsonCount(1, 'followers');
    }

    public function test_user_can_get_following_list()
    {
        $this->user1->following()->attach($this->user2->id);

        $response = $this->withHeaders($this->headers())->getJson('/api/following');

        $response->assertStatus(200)
            ->assertJsonStructure(['following'])
            ->assertJsonCount(1, 'following');
    }

    public function test_can_check_if_following_user()
    {
        $this->user1->following()->attach($this->user2->id);

        $response = $this->withHeaders($this->headers())->getJson("/api/is-following/{$this->user2->id}");

        $response->assertStatus(200)
            ->assertJson(['is_following' => true]);
    }

    public function test_prevents_duplicate_follows()
    {
        $this->user1->following()->attach($this->user2->id);

        $response = $this->withHeaders($this->headers())->postJson('/api/follow', [
            'user_id' => $this->user2->id
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Ya sigues a este usuario']);
    }
}
