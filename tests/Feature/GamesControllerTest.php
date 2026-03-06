<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Game;
use App\Models\Genre;
use App\Models\GamePlatform;
use App\Models\GameStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class GamesControllerTest extends TestCase
{
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

        // Ensure some base statuses exist
        GameStatus::firstOrCreate(['name' => 'Backlog', 'slug' => 'backlog']);
        GameStatus::firstOrCreate(['name' => 'Playing', 'slug' => 'playing']);
        GameStatus::firstOrCreate(['name' => 'Completed', 'slug' => 'completed']);
    }

    protected function headers()
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    public function test_can_list_and_filter_games()
    {
        $genre = Genre::create(['name' => 'RPG', 'slug' => 'rpg']);
        $platform = GamePlatform::create(['name' => 'PC', 'slug' => 'pc']);

        $game1 = Game::create([
            'name' => 'Zelda',
            'developer' => 'Nintendo',
            'metacritic_score' => 95,
            'release_date' => '2017-03-03'
        ]);

        $game2 = Game::create([
            'name' => 'Witcher 3',
            'developer' => 'CD Projekt Red',
            'metacritic_score' => 93,
            'release_date' => '2015-05-19'
        ]);

        $game2->genres()->attach($genre->id);
        $game2->platforms()->attach($platform->id, ['release_date' => '2015-05-19']);

        // Test list all
        $response = $this->withHeaders($this->headers())->getJson('/api/games');
        $response->assertStatus(200)
            ->assertJsonPath('data.total', 2);

        // Test filter by name
        $response = $this->withHeaders($this->headers())->getJson('/api/games?name=Zelda');
        $response->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.name', 'Zelda');

        // Test filter by genre
        $response = $this->withHeaders($this->headers())->getJson('/api/games?genre_id=' . $genre->id);
        $response->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.name', 'Witcher 3');
    }

    public function test_can_get_single_game_with_relations()
    {
        $game = Game::create(['name' => 'Dark Souls']);
        $genre = Genre::create(['name' => 'Action RPG', 'slug' => 'action-rpg']);
        $game->genres()->attach($genre->id);

        $response = $this->withHeaders($this->headers())->getJson("/api/games/{$game->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Dark Souls')
            ->assertJsonPath('data.genres.0.name', 'Action RPG');
    }

    public function test_user_can_toggle_game_in_library()
    {
        $game = Game::create(['name' => 'Hades']);
        $platform = GamePlatform::create(['name' => 'PC', 'slug' => 'pc']);

        // Link Game
        $response = $this->withHeaders($this->headers())->postJson("/api/my-games/{$game->id}/toggle", [
            'game_platform_id' => $platform->id
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('action', 'linked');

        $this->assertDatabaseHas('user_games', [
            'user_id' => $this->user->id,
            'game_id' => $game->id,
            'game_platform_id' => $platform->id
        ]);

        // Unlink Game
        $response2 = $this->withHeaders($this->headers())->postJson("/api/my-games/{$game->id}/toggle", [
            'game_platform_id' => $platform->id
        ]);

        $response2->assertStatus(200)
            ->assertJsonPath('action', 'unlinked');

        $this->assertDatabaseMissing('user_games', [
            'user_id' => $this->user->id,
            'game_id' => $game->id,
            'game_platform_id' => $platform->id
        ]);
    }

    public function test_user_can_get_their_games()
    {
        $game = Game::create(['name' => 'Stardew Valley']);
        $status = GameStatus::where('slug', 'backlog')->first();

        DB::table('user_games')->insert([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
            'game_status_id' => $status->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders($this->headers())->getJson('/api/my-games');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Stardew Valley');
    }

    public function test_user_can_change_game_status()
    {
        $game = Game::create(['name' => 'Elden Ring']);
        $statusBacklog = GameStatus::where('slug', 'backlog')->first();

        DB::table('user_games')->insert([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
            'game_status_id' => $statusBacklog->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders($this->headers())->patchJson("/api/my-games/{$game->id}/status", [
            'status' => 'playing'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status_name', 'Playing');

        $statusPlaying = GameStatus::where('slug', 'playing')->first();

        $this->assertDatabaseHas('user_games', [
            'user_id' => $this->user->id,
            'game_id' => $game->id,
            'game_status_id' => $statusPlaying->id
        ]);
    }

    public function test_can_update_game_properties()
    {
        $game = Game::create(['name' => 'Old Name']);
        $genre = Genre::create(['name' => 'FPS', 'slug' => 'fps']);

        $headers = array_merge($this->headers(), [
            'Admin-Authorization' => env('ADMIN_TOKEN', 'testadmin123')
        ]);

        $response = $this->withHeaders($headers)->putJson("/api/games/{$game->id}", [
            'name' => 'New Name',
            'metacritic_score' => 88,
            'genres' => [$genre->id]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('games', [
            'id' => $game->id,
            'name' => 'New Name',
            'metacritic_score' => 88
        ]);

        $this->assertDatabaseHas('game_genre', [
            'game_id' => $game->id,
            'genre_id' => $genre->id
        ]);
    }
}
