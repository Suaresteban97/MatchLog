<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SocialConnectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Social Platforms (Identity)
        $socials = [
            ['name' => 'Steam', 'icon_url' => 'steam.png'],
            ['name' => 'Xbox Live', 'icon_url' => 'xbox.png'],
            ['name' => 'PlayStation Network', 'icon_url' => 'psn.png'],
            ['name' => 'Discord', 'icon_url' => 'discord.png'],
            ['name' => 'Nintendo Network', 'icon_url' => 'nintendo.png'],
            ['name' => 'Twitch', 'icon_url' => 'twitch.png'],
        ];

        foreach ($socials as $social) {
            \App\Models\SocialPlatform::firstOrCreate(['name' => $social['name']], $social);
        }

        // Execution Platforms (Services)
        $executions = [
            ['name' => 'Steam Client', 'icon_url' => 'steam.png'],
            ['name' => 'Xbox Game Pass', 'icon_url' => 'gamepass.png'],
            ['name' => 'Nvidia GeForce Now', 'icon_url' => 'gfn.png'],
            ['name' => 'Epic Games Launcher', 'icon_url' => 'epic.png'],
            ['name' => 'EA App', 'icon_url' => 'ea.png'],
            ['name' => 'Ubisoft Connect', 'icon_url' => 'ubisoft.png'],
            ['name' => 'GOG Galaxy', 'icon_url' => 'gog.png'],
        ];

        foreach ($executions as $exec) {
            \App\Models\ExecutionPlatform::firstOrCreate(['name' => $exec['name']], $exec);
        }
    }
}
