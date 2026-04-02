<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_states', function (Blueprint $table) {
            $table->id();
            $table->string('source')->unique()->comment('E.g. rawg_games, igdb_games');
            $table->unsignedInteger('last_page')->default(0);
            $table->unsignedInteger('total_pages')->nullable();
            $table->unsignedInteger('total_items')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_states');
    }
};
