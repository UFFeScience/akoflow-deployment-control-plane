<?php

namespace App\Services;

use App\Repositories\OrganizationUserRepository;

class ListOrganizationMembersService
{
    public function __construct(private OrganizationUserRepository $organizationUserRepository)
    {}

    public function execute(int $organizationId)
    {
        return $this->organizationUserRepository->getByOrganizationId($organizationId);
    }
}
