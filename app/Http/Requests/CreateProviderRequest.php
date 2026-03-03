<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Provider;

class CreateProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100|unique:providers,slug',
            'description' => 'nullable|string',
            'type' => ['required','string', function($attr, $value, $fail) {
                if (!in_array($value, Provider::TYPES, true)) {
                    $fail('Invalid provider type');
                }
            }],
            'status' => ['nullable','string', function($attr, $value, $fail) {
                if ($value && !in_array($value, Provider::STATUSES, true)) {
                    $fail('Invalid provider status');
                }
            }],
        ];
    }
}
