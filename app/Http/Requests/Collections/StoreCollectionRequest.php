<?php

namespace App\Http\Requests\Collections;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:2000'],
            'cover_image_url' => ['nullable', 'url', 'max:2048'],
            'is_public'       => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'            => 'nombre de la colección',
            'description'     => 'descripción',
            'cover_image_url' => 'imagen de portada',
            'is_public'       => 'público',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'code'    => 422,
                'message' => 'Faltan campos o están erróneos',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
