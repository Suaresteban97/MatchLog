<?php

namespace App\Http\Requests\GameSession;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreGameSessionRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'game_id' => 'nullable|exists:games,id',
            'description' => 'nullable|string|max:1000',
            'start_time' => 'required|date|after:now',
            'max_participants' => 'required|integer|min:2|max:100',
            'link' => 'nullable|url|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título de la sesión es requerido',
            'title.max' => 'El título no puede exceder 255 caracteres',
            'game_id.exists' => 'El juego seleccionado no existe',
            'description.max' => 'La descripción no puede exceder 1000 caracteres',
            'start_time.required' => 'La fecha de inicio es requerida',
            'start_time.date' => 'La fecha de inicio debe ser válida',
            'start_time.after' => 'La fecha de inicio debe ser en el futuro',
            'max_participants.required' => 'El número máximo de participantes es requerido',
            'max_participants.integer' => 'El número de participantes debe ser un entero',
            'max_participants.min' => 'Debe haber al menos 2 participantes',
            'max_participants.max' => 'No puede haber más de 100 participantes',
            'link.url' => 'El enlace debe ser una URL válida',
            'link.max' => 'El enlace no puede exceder 500 caracteres',
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
