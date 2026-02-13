<?php

namespace App\Http\Requests\SocialProfile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSocialProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'gamertag' => 'sometimes|required|string|max:255',
            'external_user_id' => 'nullable|string|max:255',
            'profile_url' => 'nullable|url|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'gamertag.required' => 'El gamertag es requerido',
            'gamertag.max' => 'El gamertag no puede exceder 255 caracteres',
            'external_user_id.max' => 'El ID externo no puede exceder 255 caracteres',
            'profile_url.url' => 'La URL del perfil debe ser válida',
            'profile_url.max' => 'La URL no puede exceder 255 caracteres',
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
