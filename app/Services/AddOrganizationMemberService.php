<?php

namespace App\Services;

use Exception;
use App\Exceptions\MemberAlreadyExistsException;
use App\Repositories\OrganizationUserRepository;
use App\Repositories\UserRepository;

class AddOrganizationMemberService
{
    public function __construct(
        private OrganizationUserRepository $organizationUserRepository,
        private UserRepository $userRepository,
    ) {}

    public function execute(int $organizationId, array $data)
    {
        $user = $this->userRepository->findById($data['user_id']);

        if (!$user) {
            throw new Exception('User not found');
        }

        $existingMember = $this->organizationUserRepository->findByUserAndOrganization(
            $data['user_id'],
            $organizationId
        );

        if ($existingMember) {
            throw new MemberAlreadyExistsException();
        }

        return $this->organizationUserRepository->create([
            'user_id' => $data['user_id'],
            'organization_id' => $organizationId,
            'role' => $data['role'] ?? 'member',
        ]);
    }
}
