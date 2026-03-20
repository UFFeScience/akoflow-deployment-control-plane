<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProviderVariableSchema;

class CreateProviderVariableSchemaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section'       => 'required|string|max:100',
            'name'          => 'required|string|max:100|regex:/^[a-z0-9_]+$/',
            'label'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'type'          => ['required', 'string', 'in:' . implode(',', ProviderVariableSchema::TYPES)],
            'required'      => 'nullable|boolean',
            'is_sensitive'  => 'nullable|boolean',
            'position'      => 'nullable|integer',
            'options'       => 'nullable|array',
            'options.*'     => 'string',
            'default_value' => 'nullable|string|max:500',
        ];
    }
}
