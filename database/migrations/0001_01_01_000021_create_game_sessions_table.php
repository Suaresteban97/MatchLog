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
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('users')->onDelete('cascade');
            $table->string('title'); // e.g., "Halo Infinite Co-op Night"
            $table->foreignId('game_id')->nullable()->constrained('games')->onDelete('set null');
            $table->text('description')->nullable();
            $table->dateTime('start_time');
            $table->integer('max_participants')->default(4);
            $table->enum('status', ['scheduled', 'active', 'finished', 'cancelled'])->default('scheduled');
            $table->string('link')->nullable(); // Discord link, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
