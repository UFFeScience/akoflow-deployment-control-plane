<?php

namespace App\Services;

use App\Repositories\OrganizationRepository;

class UpdateOrganizationService
{
    public function __construct(private OrganizationRepository $organizationRepository)
    {}

    public function execute($organization, array $data)
    {
        return $this->organizationRepository->update($organization, [
            'name' => $data['name'] ?? $organization->name,
            'description' => $data['description'] ?? $organization->description,
        ]);
    }
}
