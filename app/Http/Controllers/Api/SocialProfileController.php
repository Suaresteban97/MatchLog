<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSocialProfile;
use App\Models\SocialPlatform;
use App\Http\Requests\SocialProfile\StoreSocialProfileRequest;
use App\Http\Requests\SocialProfile\UpdateSocialProfileRequest;
use Illuminate\Http\Request;

class SocialProfileController extends Controller
{
    /**
     * Display a listing of the authenticated user's social profiles.
     */
    public function index(Request $request)
    {
        $profiles = $request->user()->socialProfiles()->with('socialPlatform')->get();

        return response()->json(['profiles' => $profiles], 200);
    }

    /**
     * Store a newly created social profile.
     */
    public function store(StoreSocialProfileRequest $request)
    {
        $profile = $request->user()->socialProfiles()->create($request->validated());
        $profile->load('socialPlatform');

        return response()->json(['profile' => $profile], 201);
    }

    /**
     * Display the specified social profile.
     */
    public function show(Request $request, $id)
    {
        $profile = $request->user()->socialProfiles()
            ->with('socialPlatform')
            ->findOrFail($id);

        return response()->json(['profile' => $profile], 200);
    }

    /**
     * Update the specified social profile.
     */
    public function update(UpdateSocialProfileRequest $request, $id)
    {
        $profile = $request->user()->socialProfiles()->findOrFail($id);
        $profile->update($request->validated());
        $profile->load('socialPlatform');

        return response()->json(['profile' => $profile], 200);
    }

    /**
     * Remove the specified social profile.
     */
    public function destroy(Request $request, $id)
    {
        $profile = $request->user()->socialProfiles()->findOrFail($id);
        $profile->delete();

        return response()->json(['message' => 'Perfil social eliminado correctamente'], 200);
    }

    /**
     * Get available social platforms.
     */
    public function platforms()
    {
        $platforms = SocialPlatform::all();
        return response()->json(['platforms' => $platforms], 200);
    }
}
