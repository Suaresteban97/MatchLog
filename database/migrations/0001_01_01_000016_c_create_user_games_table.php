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
        Schema::create('user_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('game_id')->constrained('games')->onDelete('cascade');
            $table->foreignId('game_status_id')->constrained('game_statuses')->onDelete('cascade');
            $table->foreignId('game_platform_id')->nullable()->constrained('game_platforms')->onDelete('set null');
            $table->decimal('hours_played', 8, 2)->nullable();
            $table->boolean('is_currently_playing')->default(false);
            $table->date('started_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->integer('rating')->nullable(); // 1-10
            $table->text('notes')->nullable();
            $table->timestamps();

            // User can have the same game on different platforms
            $table->unique(['user_id', 'game_id', 'game_platform_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_games');
    }
};
