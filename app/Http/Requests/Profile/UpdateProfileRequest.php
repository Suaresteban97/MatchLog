<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        $userId = $this->user()->id;

        return [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'nickname' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('info_users', 'nickname')->ignore($userId, 'user_id'),
            ],
            'bio' => 'nullable|string|max:1000',
            'age' => 'nullable|integer|min:1|max:120',
            'genre' => 'nullable|string|max:100',
            'photo' => 'nullable|string|max:255',
            'share_email' => 'nullable|boolean',
            'available_for_online' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.max' => 'El nombre no puede exceder 255 caracteres',
            'last_name.max' => 'El apellido no puede exceder 255 caracteres',
            'nickname.max' => 'El nickname no puede exceder 100 caracteres',
            'bio.max' => 'La biografía no puede exceder 1000 caracteres',
            'age.integer' => 'La edad debe ser un número',
            'age.min' => 'La edad debe ser mayor a 0',
            'age.max' => 'La edad no puede ser mayor a 120',
            'genre.max' => 'El género no puede exceder 100 caracteres',
            'photo.max' => 'La URL de la foto no puede exceder 255 caracteres',
            'share_email.boolean' => 'El campo compartir email debe ser verdadero o falso',
            'available_for_online.boolean' => 'El campo disponibilidad debe ser verdadero o falso',
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
