<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\GameSession;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GameSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
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
    }

    protected function headers()
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    public function test_user_can_create_game_session()
    {
        $sessionData = [
            'title' => 'Halo Co-op Night',
            'description' => 'Legendary campaign run',
            'start_time' => Carbon::now()->addDays(1)->toDateTimeString(),
            'max_participants' => 4,
            'link' => 'https://discord.gg/example'
        ];

        $response = $this->withHeaders($this->headers())->postJson('/api/sessions', $sessionData);

        $response->assertStatus(201)
            ->assertJsonStructure(['session']);

        $this->assertDatabaseHas('game_sessions', [
            'host_id' => $this->user->id,
            'title' => 'Halo Co-op Night'
        ]);
    }

    public function test_user_can_list_their_sessions()
    {
        GameSession::create([
            'host_id' => $this->user->id,
            'title' => 'Test Session',
            'start_time' => Carbon::now()->addDays(1),
            'max_participants' => 4
        ]);

        $response = $this->withHeaders($this->headers())->getJson('/api/sessions');

        $response->assertStatus(200)
            ->assertJsonStructure(['hosting', 'participating']);
    }

    public function test_user_can_update_their_session()
    {
        $session = GameSession::create([
            'host_id' => $this->user->id,
            'title' => 'Original Title',
            'start_time' => Carbon::now()->addDays(1),
            'max_participants' => 4
        ]);

        $response = $this->withHeaders($this->headers())->putJson("/api/sessions/{$session->id}", [
            'title' => 'Updated Title'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('game_sessions', [
            'id' => $session->id,
            'title' => 'Updated Title'
        ]);
    }

    public function test_user_can_delete_their_session()
    {
        $session = GameSession::create([
            'host_id' => $this->user->id,
            'title' => 'To Delete',
            'start_time' => Carbon::now()->addDays(1),
            'max_participants' => 4
        ]);

        $response = $this->withHeaders($this->headers())->deleteJson("/api/sessions/{$session->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('game_sessions', ['id' => $session->id]);
    }

    public function test_user_can_join_session()
    {
        $host = User::factory()->create();
        $session = GameSession::create([
            'host_id' => $host->id,
            'title' => 'Open Session',
            'start_time' => Carbon::now()->addDays(1),
            'max_participants' => 4
        ]);

        $response = $this->withHeaders($this->headers())->postJson("/api/sessions/{$session->id}/join");

        $response->assertStatus(201);
        $this->assertDatabaseHas('game_session_participants', [
            'game_session_id' => $session->id,
            'user_id' => $this->user->id
        ]);
    }

    public function test_user_can_leave_session()
    {
        $host = User::factory()->create();
        $session = GameSession::create([
            'host_id' => $host->id,
            'title' => 'Session to Leave',
            'start_time' => Carbon::now()->addDays(1),
            'max_participants' => 4
        ]);

        $session->participants()->attach($this->user->id, ['status' => 'accepted']);

        $response = $this->withHeaders($this->headers())->postJson("/api/sessions/{$session->id}/leave");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('game_session_participants', [
            'game_session_id' => $session->id,
            'user_id' => $this->user->id
        ]);
    }

    public function test_cannot_join_full_session()
    {
        $host = User::factory()->create();
        $session = GameSession::create([
            'host_id' => $host->id,
            'title' => 'Full Session',
            'start_time' => Carbon::now()->addDays(1),
            'max_participants' => 2
        ]);

        // Fill session
        $participant1 = User::factory()->create();
        $participant2 = User::factory()->create();
        $session->participants()->attach([$participant1->id, $participant2->id], ['status' => 'accepted']);

        $response = $this->withHeaders($this->headers())->postJson("/api/sessions/{$session->id}/join");

        $response->assertStatus(400)
            ->assertJson(['error' => 'La sesión está llena']);
    }

    public function test_can_browse_open_sessions()
    {
        GameSession::create([
            'host_id' => $this->user->id,
            'title' => 'Public Session',
            'start_time' => Carbon::now()->addDays(1),
            'max_participants' => 4,
            'status' => 'scheduled'
        ]);

        $response = $this->withHeaders($this->headers())->getJson('/api/sessions/browse');

        $response->assertStatus(200);
    }

    public function test_validation_fails_for_past_start_time()
    {
        $sessionData = [
            'title' => 'Invalid Session',
            'start_time' => Carbon::now()->subDay()->toDateTimeString(),
            'max_participants' => 4
        ];

        $response = $this->withHeaders($this->headers())->postJson('/api/sessions', $sessionData);

        $response->assertStatus(400)
            ->assertJsonValidationErrors(['start_time']);
    }
}
