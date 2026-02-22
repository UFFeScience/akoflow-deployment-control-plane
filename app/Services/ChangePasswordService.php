<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class ChangePasswordService
{
    public function __construct(private UserRepository $userRepository)
    {}

    public function execute($user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            return false;
        }

        $this->userRepository->update($user, [
            'password' => Hash::make($newPassword),
        ]);

        return true;
    }
}
