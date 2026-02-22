<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\HealthStatus;

class UpdateProviderHealthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'health_status' => ['required','string', function($attr, $value, $fail) {
                if (!in_array($value, array_column(HealthStatus::cases(), 'value'))) {
                    $fail('Invalid health status');
                }
            }],
            'health_message' => 'nullable|string',
            'last_health_check_at' => 'nullable|date',
        ];
    }
}
