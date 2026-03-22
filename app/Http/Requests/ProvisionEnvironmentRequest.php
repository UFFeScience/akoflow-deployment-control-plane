<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Environment;
use App\Models\Deployment;

class ProvisionEnvironmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Environment fields ──────────────────────────────────────────
            'name'                             => 'required|string|max:255',
            'description'                      => 'nullable|string|max:2000',
            'status'                           => ['nullable', 'string', function ($attr, $value, $fail) {
                if ($value && !in_array($value, Environment::STATUSES, true)) {
                    $fail('Invalid environment status');
                }
            }],
            'execution_mode'                   => 'nullable|string|in:manual,auto,scheduled',
            'environment_template_version_id'  => 'nullable|integer|exists:environment_template_versions,id',
            'configuration_json'               => 'nullable|array',

            // ── Deployment fields ─────────────────────────────────────────────
            'deployment'                          => 'nullable|array',
            'deployment.provider_id'              => 'required_with:deployment|integer|exists:providers,id',
            'deployment.provider_credential_id'    => 'nullable|integer|exists:provider_credentials,id',
            'deployment.region'                   => 'nullable|string',
            'deployment.environment_type'         => ['nullable', 'string', function ($attr, $value, $fail) {
                if ($value === null) return;
                if (!in_array($value, Deployment::ENVIRONMENT_TYPES, true)) {
                    $fail('Invalid deployment environment type');
                }
            }],
            'deployment.name'                        => 'nullable|string|max:255',
            'deployment.deployment_template_id'      => 'nullable|integer|exists:deployment_templates,id',
        ];
    }
}
