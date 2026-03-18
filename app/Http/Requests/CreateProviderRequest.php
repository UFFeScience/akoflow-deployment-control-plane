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
        $organizationId = $this->route('organizationId');

        return [
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:100',
                function ($attr, $value, $fail) use ($organizationId) {
                    if ($value && Provider::where('organization_id', $organizationId)->where('slug', $value)->exists()) {
                        $fail('The slug has already been taken for this organization.');
                    }
                },
            ],
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
