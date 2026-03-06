<?php

namespace App\Http\Requests\Games;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateGameRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'cover_image_url' => 'nullable|url',
            'release_date' => 'nullable|date',
            'developer' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'metacritic_score' => 'nullable|integer|min:0|max:100',
            'is_multiplayer' => 'nullable|boolean',
            'is_online_multiplayer' => 'nullable|boolean',
            'is_local_multiplayer' => 'nullable|boolean',
            'is_cooperative' => 'nullable|boolean',
            'max_players' => 'nullable|integer|min:1',
            'genres' => 'nullable|array',
            'genres.*' => 'integer|exists:genres,id',
            'platforms' => 'nullable|array',
            'platforms.*' => 'integer|exists:game_platforms,id'
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                "code" => 400,
                "message" => "Faltan campos o están erróneos",
                "errors" => $validator->errors(),
            ], 400)
        );
    }
}
