<?php

namespace App\Services;

use App\Repositories\OrganizationRepository;
use App\Repositories\OrganizationUserRepository;

class CreateOrganizationService
{
    public function __construct(
        private OrganizationRepository $organizationRepository,
        private OrganizationUserRepository $organizationUserRepository,
    ) {}

    public function execute($user, array $data)
    {
        $organization = $this->organizationRepository->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        // Add creator as owner
        $this->organizationUserRepository->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'owner',
        ]);

        return $organization;
    }
}
