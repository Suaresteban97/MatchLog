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
        Schema::create('user_social_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('social_platform_id')->constrained('social_platforms')->onDelete('cascade');
            $table->string('gamertag'); // e.g., "Teban97"
            $table->string('external_user_id')->nullable(); // System ID from the platform
            $table->string('profile_url')->nullable(); // e.g., "steamcommunity.com/id/..."
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_social_profiles');
    }
};
