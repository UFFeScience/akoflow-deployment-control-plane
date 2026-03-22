<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Deployment;

class CreateClusterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cluster_template_id' => 'nullable|integer|exists:cluster_templates,id',
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
            'instance_groups' => 'nullable|array|min:1',
            'instance_groups.*.instance_type_id' => 'required_with:instance_groups|integer|exists:instance_types,id',
            'instance_groups.*.role' => 'nullable|string|max:100',
            'instance_groups.*.quantity' => 'required_with:instance_groups|integer|min:1',
            'instance_groups.*.metadata' => 'nullable|array',
            // backwards compatibility
            'instances' => 'nullable|array|min:1',
            'instances.*.instance_type_id' => 'required_with:instances|integer|exists:instance_types,id',
            'instances.*.role' => 'nullable|string|max:100',
            'instances.*.quantity' => 'required_with:instances|integer|min:1',
            'node_count' => 'nullable|integer|min:1',
        ];
    }
}
