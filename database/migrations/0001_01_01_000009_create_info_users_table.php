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
        Schema::create('info_users', function (Blueprint $table) {
            $table->id();
            $table->string("first_name")->nullable();
            $table->string("last_name")->nullable();
            $table->integer("age")->nullable();
            $table->string("genre", 100)->nullable();
            $table->string("nickname", 100)->nullable();
            $table->text("bio")->nullable(); // Biografía del usuario
            $table->string("photo", 100)->nullable();
            $table->boolean("share_email")->default(false); // Privacidad: compartir email
            $table->boolean("available_for_online")->default(true); // Disponibilidad para juego online
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('info_users');
    }
};
