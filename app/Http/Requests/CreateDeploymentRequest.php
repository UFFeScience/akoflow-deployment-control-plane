<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Deployment;

class CreateDeploymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deployment_template_id' => 'nullable|integer|exists:deployment_templates,id',
            'provider_id' => 'required|integer|exists:providers,id',
            'provider_credential_id' => 'nullable|integer|exists:provider_credentials,id',
            'region' => 'nullable|string',
            'environment_type' => ['nullable','string', function($attr, $value, $fail) {
                if ($value === null) return;
                if (!in_array($value, Deployment::ENVIRONMENT_TYPES, true)) {
                    $fail('Invalid environment type');
                }
            }],
            'name' => 'nullable|string|max:255',
        ];
    }
}
