<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasswordRulesController extends Controller
{
    /**
     * Return the password rules config as JSON for frontend consumption.
     */
    public function rules(Request $request): JsonResponse
    {
        $config = config('password_rules');

        return response()->json([
            'rules' => $config,
        ]);
    }

    /**
     * Return a UI render description for the frontend to build the inputs.
     */
    public function render(Request $request): JsonResponse
    {
        $config = config('password_rules');

        $ui = [
            'fields' => $config['ui']['fields'] ?? [],
            'hints' => [
                'min_length' => $config['min_length'] ?? 8,
                'require_numbers' => $config['require_numbers'] ?? false,
                'require_special' => $config['require_special'] ?? false,
                'require_mixed_case' => $config['require_mixed_case'] ?? false,
            ],
        ];

        return response()->json([ 'ui' => $ui ]);
    }
}
