<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddOrganizationMemberRequest extends FormRequest
{
    use FailedValidationTrait;
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'sometimes|string|in:owner,admin,member,viewer',
        ];
    }
}
