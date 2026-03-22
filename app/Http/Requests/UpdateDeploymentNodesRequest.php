<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClusterNodesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instance_groups' => 'required|array|min:1',
            'instance_groups.*.id' => 'required|integer|exists:instance_groups,id',
            'instance_groups.*.quantity' => 'required|integer|min:0',
        ];
    }
}
