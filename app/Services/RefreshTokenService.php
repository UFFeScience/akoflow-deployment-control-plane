<?php

namespace App\Services;

class RefreshTokenService
{
    public function execute($user): array
    {
        // Revoke old token
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
