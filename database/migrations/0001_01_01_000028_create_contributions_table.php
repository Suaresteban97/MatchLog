<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the polymorphic `contributions` table used by the Community
     * Collaboration module. A contribution represents a user-proposed change
     * to a Game, Genre, or GamePlatform record that must be reviewed before
     * being applied to the main tables.
     */
    public function up(): void
    {
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->morphs('contributable');
            $table->string('field');
            $table->text('current_value')->nullable();
            $table->text('proposed_value');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->index('status');
            $table->foreignId('reviewer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
