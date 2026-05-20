<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('info_users', function (Blueprint $table) {
            // Visibility on public profile tabs
            $table->boolean('show_posts')->default(true)->after('available_for_online');
            $table->boolean('show_backlog')->default(true)->after('show_posts');
            $table->boolean('show_collections')->default(true)->after('show_backlog');
            $table->boolean('show_groups')->default(true)->after('show_collections');
            $table->boolean('show_social_profiles')->default(true)->after('show_groups');
            $table->boolean('show_devices')->default(true)->after('show_social_profiles');
        });
    }

    public function down(): void
    {
        Schema::table('info_users', function (Blueprint $table) {
            $table->dropColumn([
                'show_posts',
                'show_backlog',
                'show_collections',
                'show_groups',
                'show_social_profiles',
                'show_devices',
            ]);
        });
    }
};
