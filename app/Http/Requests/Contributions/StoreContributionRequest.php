<?php

namespace App\Http\Requests\Contributions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreContributionRequest extends FormRequest
{
    /**
     * Any authenticated user can submit a contribution.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for submitting a community contribution.
     */
    public function rules(): array
    {
        return [
            'contributable_type' => ['required', 'string', 'in:game,genre,platform'],
            'contributable_id'   => ['required', 'integer', 'min:1'],
            'field'              => ['required', 'string', 'max:100'],
            'proposed_value'     => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * Human-readable attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'contributable_type' => 'tipo de recurso',
            'contributable_id'   => 'ID del recurso',
            'field'              => 'campo',
            'proposed_value'     => 'valor propuesto',
        ];
    }

    /**
     * Return a JSON error response consistent with the rest of the API.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'code'    => 400,
                'message' => 'Faltan campos o están erróneos',
                'errors'  => $validator->errors(),
            ], 400)
        );
    }
}
