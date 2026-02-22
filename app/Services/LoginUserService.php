<?php

namespace App\Services;

use App\Exceptions\InvalidPasswordException;
use App\Exceptions\UserNotFoundException;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class LoginUserService
{
    public function __construct(private UserRepository $userRepository)
    {}

    public function execute(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            throw new UserNotFoundException('User not found');
        }

        if (!Hash::check($password, $user->password)) {
            throw new InvalidPasswordException('Invalid password');
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
