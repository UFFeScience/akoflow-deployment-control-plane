<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\InstanceType;

class CreateInstanceTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_id' => 'required|integer|exists:providers,id',
            'name' => 'required|string|max:255',
            'vcpus' => 'nullable|integer',
            'memory_mb' => 'nullable|integer',
            'gpu_count' => 'nullable|integer',
            'storage_default_gb' => 'nullable|integer',
            'network_bandwidth' => 'nullable|string',
            'region' => 'nullable|string',
            'status' => ['nullable','string', function($attr, $value, $fail) {
                if ($value && !in_array($value, InstanceType::STATUSES, true)) {
                    $fail('Invalid status');
                }
            }],
            'is_active' => 'nullable|boolean',
        ];
    }
}
