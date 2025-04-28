<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('devices')->insert([
            ['name' => 'PC'],
            ['name' => 'Laptop'],
            ['name' => 'Steam Deck'],
            ['name' => 'ROG Ally'],
            ['name' => 'Xbox One'],
            ['name' => 'Xbox One S'],
            ['name' => 'Xbox One X'],
            ['name' => 'Xbox Series S'],
            ['name' => 'Xbox Series X'],
            ['name' => 'PlayStation 4'],
            ['name' => 'PlayStation 4 Slim'],
            ['name' => 'PlayStation 4 Pro'],
            ['name' => 'PlayStation 5'],
            ['name' => 'Nintendo Switch'],
            ['name' => 'Nintendo Switch OLED'],
            ['name' => 'Nintendo Switch Lite'],
            ['name' => 'PlayStation Vita'],
            ['name' => 'PlayStation Portable (PSP)'],
            ['name' => 'Nintendo 3DS'],
            ['name' => 'Nintendo 2DS'],
            ['name' => 'Wii U'],
            ['name' => 'Wii'],
            ['name' => 'GameCube'],
            ['name' => 'PlayStation 3'],
            ['name' => 'PlayStation 2'],
            ['name' => 'PlayStation 1'],
            ['name' => 'Xbox 360'],
            ['name' => 'Xbox Original'],
            ['name' => 'Android'],
            ['name' => 'iOS'],
            ['name' => 'Mac'],
            ['name' => 'NVIDIA Shield'],
            ['name' => 'Amazon Luna'],
            ['name' => 'Google Stadia'],
            ['name' => 'Atari VCS'],
            ['name' => 'Retro Console'],
            ['name' => 'Arcade Machine'],
            ['name' => 'Browser'],
            ['name' => 'Smart TV'],
            ['name' => 'VR Headset (Oculus Quest, Valve Index, etc.)'],
        ]);
        
    }
}
