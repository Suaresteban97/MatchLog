<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GamePlatform;

class GamePlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $platforms = [
            'PC',
            'PlayStation',
            'Xbox',
            'Nintendo Switch',
            'Steam Deck',
            'Mobile',
        ];

        foreach ($platforms as $platform) {
            GamePlatform::firstOrCreate(['name' => $platform]);
        }
    }
}
