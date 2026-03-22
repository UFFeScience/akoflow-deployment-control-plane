<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ClusterScalingEvent;

class ScaleClusterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required','string', function($attr, $value, $fail) {
                if (!in_array($value, ClusterScalingEvent::ACTIONS, true)) {
                    $fail('Invalid scale action');
                }
            }],
            'old_value' => 'required|integer',
            'new_value' => 'required|integer',
            'triggered_by' => ['required','string', function($attr, $value, $fail) {
                if (!in_array($value, ClusterScalingEvent::TRIGGERED_BY, true)) {
                    $fail('Invalid trigger source');
                }
            }],
        ];
    }
}
