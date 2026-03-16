<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'runtime_type' => 'nullable|string',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
            'owner_organization_id' => 'nullable|integer|exists:organizations,id',
        ];
    }
}
