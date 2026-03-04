<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Game;
use App\Models\Genre;
use App\Models\GamePlatform;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $games = [
            [
                'name' => 'Halo Infinite',
                'description' => 'First-person shooter game developed by 343 Industries',
                'release_date' => '2021-12-08',
                'developer' => '343 Industries',
                'publisher' => 'Xbox Game Studios',
                'metacritic_score' => 87,
                'is_multiplayer' => true,
                'is_online_multiplayer' => true,
                'is_local_multiplayer' => false,
                'is_cooperative' => true,
                'max_players' => 24,
                'genres' => ['FPS', 'Action'],
                'platforms' => ['PC', 'Xbox'],
            ],
            [
                'name' => 'Call of Duty: Modern Warfare III',
                'description' => 'First-person shooter game in the Call of Duty franchise',
                'release_date' => '2023-11-10',
                'developer' => 'Sledgehammer Games',
                'publisher' => 'Activision',
                'metacritic_score' => 68,
                'is_multiplayer' => true,
                'is_online_multiplayer' => true,
                'is_local_multiplayer' => false,
                'is_cooperative' => true,
                'max_players' => 150,
                'genres' => ['FPS', 'Action'],
                'platforms' => ['PC', 'Xbox', 'PlayStation'],
            ],
            [
                'name' => 'Minecraft',
                'description' => 'Sandbox video game with blocky 3D graphics',
                'release_date' => '2011-11-18',
                'developer' => 'Mojang Studios',
                'publisher' => 'Microsoft',
                'metacritic_score' => 93,
                'is_multiplayer' => true,
                'is_online_multiplayer' => true,
                'is_local_multiplayer' => true,
                'is_cooperative' => true,
                'max_players' => null,
                'genres' => ['Sandbox', 'Survival'],
                'platforms' => ['PC', 'Xbox', 'PlayStation', 'Nintendo Switch'],
            ],
            [
                'name' => 'Fortnite',
                'description' => 'Battle royale game developed by Epic Games',
                'release_date' => '2017-09-26',
                'developer' => 'Epic Games',
                'publisher' => 'Epic Games',
                'metacritic_score' => 78,
                'is_multiplayer' => true,
                'is_online_multiplayer' => true,
                'is_local_multiplayer' => false,
                'is_cooperative' => false,
                'max_players' => 100,
                'genres' => ['Battle Royale', 'Action'],
                'platforms' => ['PC', 'Xbox', 'PlayStation', 'Nintendo Switch'],
            ],
            [
                'name' => 'League of Legends',
                'description' => 'Multiplayer online battle arena game',
                'release_date' => '2009-10-27',
                'developer' => 'Riot Games',
                'publisher' => 'Riot Games',
                'metacritic_score' => 78,
                'is_multiplayer' => true,
                'is_online_multiplayer' => true,
                'is_local_multiplayer' => false,
                'is_cooperative' => false,
                'max_players' => 10,
                'genres' => ['MOBA', 'Strategy'],
                'platforms' => ['PC'],
            ],
            [
                'name' => 'Valorant',
                'description' => 'Tactical first-person shooter',
                'release_date' => '2020-06-02',
                'developer' => 'Riot Games',
                'publisher' => 'Riot Games',
                'metacritic_score' => 80,
                'is_multiplayer' => true,
                'is_online_multiplayer' => true,
                'is_local_multiplayer' => false,
                'is_cooperative' => false,
                'max_players' => 10,
                'genres' => ['FPS', 'Tactical'],
                'platforms' => ['PC'],
            ],
        ];

        foreach ($games as $gameData) {
            $genreNames = $gameData['genres'];
            $platformNames = $gameData['platforms'];
            unset($gameData['genres'], $gameData['platforms']);

            $game = Game::firstOrCreate(
                ['name' => $gameData['name']],
                $gameData
            );

            // Attach genres
            foreach ($genreNames as $genreName) {
                $genre = Genre::firstOrCreate(
                    ['name' => $genreName],
                    ['slug' => Str::slug($genreName)]
                );
                $game->genres()->syncWithoutDetaching($genre->id);
            }

            // Attach platforms
            foreach ($platformNames as $platformName) {
                $platform = GamePlatform::where('name', $platformName)->first();
                if ($platform) {
                    $game->platforms()->syncWithoutDetaching($platform->id);
                }
            }
        }
    }
}
