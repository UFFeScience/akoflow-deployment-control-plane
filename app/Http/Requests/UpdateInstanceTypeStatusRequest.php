<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\InstanceTypeStatus;

class UpdateInstanceTypeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required','string', function($attr, $value, $fail) {
                if (!in_array($value, array_column(InstanceTypeStatus::cases(), 'value'))) {
                    $fail('Invalid status');
                }
            }]
        ];
    }
}
