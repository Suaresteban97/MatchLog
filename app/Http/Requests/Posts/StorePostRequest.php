<?php

namespace App\Http\Requests\Posts;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // We assume the user is authenticated via middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string|max:1000',
            'game_id' => 'nullable|exists:games,id',
            'collection_id' => 'nullable|exists:collections,id',
            'game_session_id' => 'nullable|exists:game_sessions,id',
            'user_device_id' => 'nullable|exists:user_devices,id',
            'share_social_profile' => 'boolean'
        ];
    }
}
