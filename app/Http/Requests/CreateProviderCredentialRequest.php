<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProviderCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'slug'        => 'nullable|string|max:100|regex:/^[a-z0-9_-]+$/',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
            'values'      => 'required|array',
            'values.*'    => 'nullable|string',
        ];
    }
}
