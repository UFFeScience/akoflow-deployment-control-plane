<?php

namespace App\Services;

use Exception;
use App\Repositories\OrganizationUserRepository;

class RemoveOrganizationMemberService
{
    public function __construct(private OrganizationUserRepository $organizationUserRepository)
    {}

    public function execute(int $organizationId, int $userId): bool
    {
        $member = $this->organizationUserRepository->findByUserAndOrganization($userId, $organizationId);

        if (!$member) {
            throw new Exception('Member not found');
        }

        return $this->organizationUserRepository->delete($member);
    }
}
