<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            'games',
            'sessionsHosting',
            'sessionsParticipating',
            'collections',
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

        return Inertia::render('Frontend/Profile/Show', [
            'userProfile' => $user,
            'visibility'  => $visibility,
        ]);
    }
}
