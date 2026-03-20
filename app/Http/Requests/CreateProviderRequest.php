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
                    $fail('Invalid provider type. Allowed: ' . implode(', ', Provider::TYPES));
                }
            }],
            'status' => ['nullable','string', function($attr, $value, $fail) {
                if ($value && !in_array($value, Provider::STATUSES, true)) {
                    $fail('Invalid provider status');
                }
            }],
            // Variable schemas defined inline at provider creation time
            'variable_schemas'                 => 'nullable|array',
            'variable_schemas.*.section'       => 'required_with:variable_schemas|string|max:100',
            'variable_schemas.*.name'          => 'required_with:variable_schemas|string|max:100|regex:/^[a-z0-9_]+$/',
            'variable_schemas.*.label'         => 'required_with:variable_schemas|string|max:255',
            'variable_schemas.*.description'   => 'nullable|string',
            'variable_schemas.*.type'          => ['required_with:variable_schemas', 'string', 'in:string,select,secret,boolean,textarea,number'],
            'variable_schemas.*.required'      => 'nullable|boolean',
            'variable_schemas.*.is_sensitive'  => 'nullable|boolean',
            'variable_schemas.*.position'      => 'nullable|integer',
            'variable_schemas.*.options'       => 'nullable|array',
            'variable_schemas.*.options.*'     => 'string',
            'variable_schemas.*.default_value' => 'nullable|string|max:500',
        ];
    }
}
