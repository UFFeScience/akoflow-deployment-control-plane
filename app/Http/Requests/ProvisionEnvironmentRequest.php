<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Environment;
use App\Models\Cluster;

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

            // ── Cluster fields ─────────────────────────────────────────────
            'cluster'                          => 'nullable|array',
            'cluster.provider_id'              => 'required_with:cluster|integer|exists:providers,id',
            'cluster.region'                   => 'nullable|string',
            'cluster.environment_type'         => ['nullable', 'string', function ($attr, $value, $fail) {
                if ($value === null) return;
                if (!in_array($value, Cluster::ENVIRONMENT_TYPES, true)) {
                    $fail('Invalid cluster environment type');
                }
            }],
            'cluster.name'                     => 'nullable|string|max:255',
            'cluster.cluster_template_id'      => 'nullable|integer|exists:cluster_templates,id',
            'cluster.node_count'               => 'nullable|integer|min:1',
            'cluster.instance_groups'          => 'nullable|array|min:1',
            'cluster.instance_groups.*.instance_type_id'        => 'required_with:cluster.instance_groups|integer|exists:instance_types,id',
            'cluster.instance_groups.*.instance_group_template_id' => 'nullable|integer|exists:instance_group_templates,id',
            'cluster.instance_groups.*.role'                    => 'nullable|string|max:100',
            'cluster.instance_groups.*.quantity'                => 'required_with:cluster.instance_groups|integer|min:1',
            'cluster.instance_groups.*.metadata'                => 'nullable|array',
            'cluster.instance_groups.*.terraform_variables'     => 'nullable|array',
            'cluster.instance_groups.*.lifecycle_hooks'         => 'nullable|array',
        ];
    }
}
