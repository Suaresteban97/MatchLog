<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
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
            'name' => 'required|string',
            'last_name' => 'required|string',
            'nickname' => 'required|string|unique:info_users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8'
        ];
    }

    /**
     * Mensajes de error personalizados.
     */
    public function messages()
    {
        return [
            'name.required' => 'Se requiere el nombre',
            'last_name.required' => 'Se requiere el apellido',
            'email.required' => 'Se requiere el email',
            'email.email' => 'Debe ser un correo válido',
            'email.unique' => 'Este correo ya existe',
            'nickname.required' => 'Se requiere un nickname',
            'nickname.unique' => 'Este nickname ya está en uso',
            'password.required' => 'Se requiere una contraseña',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres'
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
