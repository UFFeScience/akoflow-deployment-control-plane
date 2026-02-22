<?php

namespace App\Services;

use App\Repositories\OrganizationRepository;

class DeleteOrganizationService
{
    public function __construct(private OrganizationRepository $organizationRepository)
    {}

    public function execute($organization): bool
    {
        return $this->organizationRepository->delete($organization);
    }
}
