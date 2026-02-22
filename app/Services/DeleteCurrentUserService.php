<?php

namespace App\Services;

use App\Repositories\UserRepository;

class DeleteCurrentUserService
{
    public function __construct(private UserRepository $userRepository)
    {}

    public function execute($user): bool
    {
        return $this->userRepository->delete($user);
    }
}
