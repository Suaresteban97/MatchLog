<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GameStatus;

class GameStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'Playing', 'slug' => 'playing'],
            ['name' => 'Completed', 'slug' => 'completed'],
            ['name' => 'Backlog', 'slug' => 'backlog'],
            ['name' => 'Wishlist', 'slug' => 'wishlist'],
            ['name' => 'Dropped', 'slug' => 'dropped'],
            ['name' => 'On Hold', 'slug' => 'on-hold'],
        ];

        foreach ($statuses as $status) {
            GameStatus::firstOrCreate(['slug' => $status['slug']], $status);
        }
    }
}
