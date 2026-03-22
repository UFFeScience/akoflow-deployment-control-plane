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
            'deployment.region'                   => 'nullable|string',
            'deployment.environment_type'         => ['nullable', 'string', function ($attr, $value, $fail) {
                if ($value === null) return;
                if (!in_array($value, Deployment::ENVIRONMENT_TYPES, true)) {
                    $fail('Invalid deployment environment type');
                }
            }],
            'deployment.name'                     => 'nullable|string|max:255',
            'deployment.cluster_template_id'      => 'nullable|integer|exists:cluster_templates,id',
            'deployment.node_count'               => 'nullable|integer|min:1',
            'deployment.instance_groups'          => 'nullable|array|min:1',
            'deployment.instance_groups.*.instance_type_id'        => 'required_with:deployment.instance_groups|integer|exists:instance_types,id',
            'deployment.instance_groups.*.instance_group_template_id' => 'nullable|integer|exists:instance_group_templates,id',
            'deployment.instance_groups.*.role'                    => 'nullable|string|max:100',
            'deployment.instance_groups.*.quantity'                => 'required_with:deployment.instance_groups|integer|min:1',
            'deployment.instance_groups.*.metadata'                => 'nullable|array',
            'deployment.instance_groups.*.terraform_variables'     => 'nullable|array',
            'deployment.instance_groups.*.lifecycle_hooks'         => 'nullable|array',
        ];
    }
}
