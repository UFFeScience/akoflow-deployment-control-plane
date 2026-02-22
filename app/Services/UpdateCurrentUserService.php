<?php

namespace App\Services;

use App\Repositories\UserRepository;

class UpdateCurrentUserService
{
    public function __construct(private UserRepository $userRepository)
    {}

    public function execute($user, array $data)
    {
        return $this->userRepository->update($user, [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
        ]);
    }
}
