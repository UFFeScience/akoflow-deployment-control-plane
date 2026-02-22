<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateExperimentTemplateVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => 'required|string|max:100',
            'definition_json' => 'required|array',
            'is_active' => 'nullable|boolean',
        ];
    }
}
