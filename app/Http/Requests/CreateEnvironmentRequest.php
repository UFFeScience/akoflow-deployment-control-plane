<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Environment;

class CreateEnvironmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'status' => ['nullable', 'string', function ($attr, $value, $fail) {
                if ($value && !in_array($value, Environment::STATUSES, true)) {
                    $fail('Invalid environment status');
                }
            }],
            'execution_mode' => 'nullable|string|in:manual,auto,scheduled',
            'environment_template_version_id' => 'nullable|integer|exists:environment_template_versions,id',
            'configuration_json' => 'nullable|array',
        ];
    }
}
