<?php

namespace App\Http\Requests\Games;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'game_status_id' => 'nullable|exists:game_statuses,id',
            'game_platform_id' => 'nullable|exists:game_platforms,id',
            'hours_played' => 'nullable|numeric|min:0',
            'rating' => 'nullable|integer|min:1|max:100',
            'is_currently_playing' => 'nullable|boolean',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ];
    }

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
