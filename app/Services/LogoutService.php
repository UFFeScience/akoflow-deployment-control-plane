<?php

namespace App\Services;

class LogoutService
{
    public function execute($user): bool
    {
        $user->tokens()->delete();
        return true;
    }
}
