<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ExperimentTemplate;

class CreateExperimentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:experiment_templates,slug',
            'runtime_type' => ['required','string', function($attr, $value, $fail) {
                if (!in_array($value, ExperimentTemplate::RUNTIME_TYPES, true)) {
                    $fail('Invalid runtime type');
                }
            }],
            'description' => 'nullable|string',
            'is_public' => 'boolean',
            'owner_organization_id' => 'nullable|integer|exists:organizations,id',
        ];
    }
}
