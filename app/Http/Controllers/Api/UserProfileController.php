<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\InfoUser;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function show(Request $request)
    {
        $profile = InfoUser::where('user_id', $request->user()->id)->first();

        if (!$profile) {
            return response()->json(['message' => 'Perfil no encontrado'], 404);
        }

        return response()->json(['profile' => $profile], 200);
    }

    public function update(UpdateProfileRequest $request)
    {
        $profile = InfoUser::firstOrNew(['user_id' => $request->user()->id]);

        $fields = [
            'first_name', 'last_name', 'nickname', 'bio', 'age', 'genre',
            'photo', 'share_email', 'available_for_online',
            'show_posts', 'show_backlog', 'show_collections',
            'show_groups', 'show_social_profiles', 'show_devices',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $profile->$field = $request->$field;
            }
        }

        $profile->user_id = $request->user()->id;
        $profile->save();

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'profile' => $profile,
        ], 200);
    }
}
