<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Experiment;

class CreateExperimentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'status' => ['nullable','string', function($attr, $value, $fail) {
                if ($value && !in_array($value, Experiment::STATUSES, true)) {
                    $fail('Invalid experiment status');
                }
            }],
        ];
    }
}
