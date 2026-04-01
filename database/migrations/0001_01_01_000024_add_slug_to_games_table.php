<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        // Generate slugs for existing games
        $games = DB::table('games')->get();
        foreach ($games as $game) {
            $baseSlug = Str::slug($game->name);
            $slug = $baseSlug;
            $count = 1;
            while (DB::table('games')->where('slug', $slug)->where('id', '!=', $game->id)->exists()) {
                $slug = $baseSlug . '-' . $count++;
            }
            DB::table('games')->where('id', $game->id)->update(['slug' => $slug]);
        }

        // Now enforce constraints
        Schema::table('games', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
