<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\InfoUser;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    /**
     * Get the authenticated user's profile
     */
    public function show(Request $request)
    {
        $profile = InfoUser::where('user_id', $request->user()->id)->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Perfil no encontrado'
            ], 404);
        }

        return response()->json([
            'profile' => $profile
        ], 200);
    }

    /**
     * Update the authenticated user's profile
     */
    public function update(UpdateProfileRequest $request)
    {
        $profile = InfoUser::where('user_id', $request->user()->id)->first();

        if (!$profile) {
            $profile = new InfoUser();
            $profile->user_id = $request->user()->id;
        }

        if ($request->has('first_name')) {
            $profile->first_name = $request->first_name;
        }

        if ($request->has('last_name')) {
            $profile->last_name = $request->last_name;
        }

        if ($request->has('nickname')) {
            $profile->nickname = $request->nickname;
        }

        if ($request->has('bio')) {
            $profile->bio = $request->bio;
        }

        if ($request->has('age')) {
            $profile->age = $request->age;
        }

        if ($request->has('genre')) {
            $profile->genre = $request->genre;
        }

        if ($request->has('photo')) {
            $profile->photo = $request->photo;
        }

        if ($request->has('share_email')) {
            $profile->share_email = $request->share_email;
        }

        if ($request->has('available_for_online')) {
            $profile->available_for_online = $request->available_for_online;
        }

        $profile->save();

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'profile' => $profile
        ], 200);
    }
}
