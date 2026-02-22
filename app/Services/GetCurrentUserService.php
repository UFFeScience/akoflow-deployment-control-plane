<?php

namespace App\Services;

use App\Repositories\UserRepository;

class GetCurrentUserService
{
    public function __construct(private UserRepository $userRepository)
    {}

    public function execute($user)
    {
        return $this->userRepository->findById($user->id);
    }
}
