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
        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('friend_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();

            // Prevent duplicate requests between same users regardless of direction
            // Note: Since direction matters for 'pending' state (who requests who),
            // a strict unique constraint might be tricky if we want to allow A to request B after B rejected A.
            // But usually, one pending request per pair is enough. We'll enforce this uniqueness.
            // Actually, a simple unique constraint prevents A->B twice:
            $table->unique(['user_id', 'friend_id']);
            
            // To prevent B->A while A->B is pending, we'd need application-level logic or a custom constraint.
            // We'll rely on application logic in the controller.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friendships');
    }
};
