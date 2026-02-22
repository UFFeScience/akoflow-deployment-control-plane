<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Cluster;

class CreateClusterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cluster_template_id' => 'required|integer|exists:cluster_templates,id',
            'provider_id' => 'required|integer|exists:providers,id',
            'region' => 'nullable|string',
            'environment_type' => ['required','string', function($attr, $value, $fail) {
                if (!in_array($value, Cluster::ENVIRONMENT_TYPES, true)) {
                    $fail('Invalid environment type');
                }
            }],
            'name' => 'required|string|max:255',
        ];
    }
}
