<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_game_platform', function (Blueprint $table) {
            $table->foreignId('game_id')->constrained('games')->onDelete('cascade');
            $table->foreignId('game_platform_id')->constrained('game_platforms')->onDelete('cascade');
            $table->date('release_date')->nullable(); // Platform-specific release date
            $table->unique(['game_id', 'game_platform_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_game_platform');
    }
};
