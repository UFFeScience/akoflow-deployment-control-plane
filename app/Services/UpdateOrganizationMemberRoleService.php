<?php

namespace App\Services;

use Exception;
use App\Repositories\OrganizationUserRepository;

class UpdateOrganizationMemberRoleService
{
    public function __construct(private OrganizationUserRepository $organizationUserRepository)
    {}

    public function execute(int $organizationId, int $userId, string $role)
    {
        $member = $this->organizationUserRepository->findByUserAndOrganization($userId, $organizationId);

        if (!$member) {
            throw new Exception('Member not found');
        }

        return $this->organizationUserRepository->update($member, [
            'role' => $role,
        ]);
    }
}
