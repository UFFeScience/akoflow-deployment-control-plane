<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{

    use FailedValidationTrait;
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $min = config('password_rules.min_length', 8);
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'string', 'min:'.$min, 'confirmed'],
        ];

        if (config('password_rules.require_numbers', true)) {
            $rules['password'][] = 'regex:/[0-9]/';
        }

        if (config('password_rules.require_special', false)) {
            $rules['password'][] = 'regex:/[!@#$%^&*()_+\-=\[\]{};:"\'\\|,.<>/?]/';
        }

        if (config('password_rules.require_mixed_case', false)) {
            $rules['password'][] = 'regex:/[a-z]/';
            $rules['password'][] = 'regex:/[A-Z]/';
        }

        return $rules;
    }


}
