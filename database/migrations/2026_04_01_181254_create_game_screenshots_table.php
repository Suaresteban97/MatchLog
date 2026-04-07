<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_screenshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('rawg_id')->nullable()->comment('RAWG screenshot ID (-1 for cover screenshot)');
            $table->string('image_url');
            $table->boolean('is_cover')->default(false)->comment('True if this is the main background/poster');
            $table->timestamps();

            $table->index('game_id');
            $table->unique(['game_id', 'rawg_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_screenshots');
    }
};
