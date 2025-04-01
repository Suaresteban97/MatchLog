<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return true; // Permite la solicitud
    }

    /**
     * Reglas de validación para el registro.
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string'
        ];
    }

    /**
     * Mensajes de error personalizados.
     */
    public function messages()
    {
        return [
            'email.required' => 'Se requiere el email',
            'email.email' => 'El email debe tener un formato válido',
            'password.required' => 'Se requiere una contraseña',
        ];
    }

    /**
     * Manejo de validación fallida.
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
