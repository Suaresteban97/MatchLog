<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateDeviceRequest extends FormRequest
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
            'custom_name' => 'nullable|string|max:100',
            'characteristics' => 'nullable|array',
            'characteristics.*.key' => 'required_with:characteristics|string|in:cpu,gpu,ram,storage',
            'characteristics.*.value' => 'nullable|string|max:255',
            'characteristics.*.pc_component_id' => 'nullable|exists:pc_components,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'custom_name.max' => 'El nombre personalizado no puede exceder 100 caracteres',
            'characteristics.array' => 'Las características deben ser un array',
            'characteristics.*.key.required_with' => 'La clave de característica es requerida',
            'characteristics.*.key.in' => 'La clave debe ser: cpu, gpu, ram o storage',
            'characteristics.*.value.max' => 'El valor no puede exceder 255 caracteres',
            'characteristics.*.pc_component_id.exists' => 'El componente seleccionado no existe',
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
