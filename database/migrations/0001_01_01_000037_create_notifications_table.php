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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');       // Who receives the notification
            $table->foreignId('sender_id')->nullable()->constrained('users')->onDelete('cascade'); // Who triggered it (null = system)
            $table->string('type');                // post_like, post_comment, comment_like, comment_reply, follow, friend_request, friend_accepted, session_invite, contribution_resolved
            $table->nullableMorphs('notifiable');   // Polymorphic: Post, PostComment, Friendship, GameSession, Contribution...
            $table->text('message')->nullable();    // Pre-built human-readable message
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
