<?php

namespace App\Services;

use App\Repositories\OrganizationRepository;

class ListOrganizationsService
{
    public function __construct(private OrganizationRepository $organizationRepository)
    {}

    public function execute($user)
    {
        return $this->organizationRepository->getByUserId($user->id);
    }
}
