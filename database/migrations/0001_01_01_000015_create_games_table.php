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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->date('release_date')->nullable();
            $table->string('developer')->nullable();
            $table->string('publisher')->nullable();
            $table->integer('metacritic_score')->nullable();
            $table->string('igdb_id')->nullable(); // External API reference
            $table->boolean('is_multiplayer')->default(false);
            $table->boolean('is_online_multiplayer')->default(false);
            $table->boolean('is_local_multiplayer')->default(false);
            $table->boolean('is_cooperative')->default(false);
            $table->unsignedInteger('max_players')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
