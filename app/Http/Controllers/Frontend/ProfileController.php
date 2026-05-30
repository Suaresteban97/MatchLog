<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\GameStatus;
use App\Models\GamePlatform;
use Inertia\Inertia;

class ProfileController extends Controller
{
    /**
     * Show edit profile form (authenticated)
     */
    public function edit()
    {
        return Inertia::render('Frontend/ProfileForm', [
            'module' => 3
        ]);
    }

    /**
     * Show public profile
     */
    public function show(User $user)
    {
        $user->load([
            'userInfo',
            'posts'  => fn($q) => $q->with('user')->latest(),
            'games.genres',
            'sessionsHosting',
            'sessionsParticipating',
            'collections.games',
            'socialProfiles.socialPlatform',
            'devices',
        ]);

        // Build visibility settings (defaults to true when no profile row exists)
        $info = $user->userInfo;
        $visibility = [
            'show_posts'            => $info ? (bool) $info->show_posts            : true,
            'show_backlog'          => $info ? (bool) $info->show_backlog          : true,
            'show_collections'      => $info ? (bool) $info->show_collections      : true,
            'show_groups'           => $info ? (bool) $info->show_groups           : true,
            'show_social_profiles'  => $info ? (bool) $info->show_social_profiles  : true,
            'show_devices'          => $info ? (bool) $info->show_devices          : true,
        ];

        // Calculate Gaming Stats
        $allStatuses = GameStatus::all()->keyBy('id');
        $allPlatforms = GamePlatform::all()->keyBy('id');

        $totalHours = 0;
        $statusCounts = [];
        $genreCounts = [];
        $platformCounts = [];
        $currentlyPlaying = null;

        foreach ($user->games as $game) {
            $pivot = $game->pivot;

            $totalHours += (float) $pivot->hours_played;

            if ($pivot->is_currently_playing && !$currentlyPlaying) {
                $currentlyPlaying = $game;
            }

            if ($pivot->game_status_id) {
                $statusName = $allStatuses->get($pivot->game_status_id)?->name ?? 'Unknown';
                if (!isset($statusCounts[$statusName])) $statusCounts[$statusName] = 0;
                $statusCounts[$statusName]++;
            }

            if ($pivot->game_platform_id) {
                $platformName = $allPlatforms->get($pivot->game_platform_id)?->name ?? 'Unknown';
                if (!isset($platformCounts[$platformName])) $platformCounts[$platformName] = 0;
                $platformCounts[$platformName]++;
            }

            foreach ($game->genres as $genre) {
                if (!isset($genreCounts[$genre->name])) $genreCounts[$genre->name] = 0;
                $genreCounts[$genre->name]++;
            }
        }

        arsort($genreCounts);
        arsort($platformCounts);

        $gamingStats = [
            'total_hours_played' => $totalHours,
            'games_by_status'    => $statusCounts,
            'most_played_genre'  => key($genreCounts) ?? 'N/A',
            'most_used_platform' => key($platformCounts) ?? 'N/A',
            'currently_playing'  => $currentlyPlaying,
        ];

        return Inertia::render('Frontend/Profile/Show', [
            'userProfile' => $user,
            'visibility'  => $visibility,
            'gamingStats' => $gamingStats,
        ]);
    }
}
