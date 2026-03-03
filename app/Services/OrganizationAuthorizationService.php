<?php

namespace App\Services;

use App\Exceptions\OrganizationNotFoundException;
use App\Exceptions\UnauthorizedOrganizationAccessException;
use App\Models\User;
use App\Repositories\OrganizationRepository;
use App\Repositories\OrganizationUserRepository;

class OrganizationAuthorizationService
{
    public function __construct(
        private OrganizationRepository $organizationRepository,
        private OrganizationUserRepository $organizationUserRepository,
    ) {}

    /**
     * Assert that the given user is an owner or member of the organization.
     *
     * @throws OrganizationNotFoundException
     * @throws UnauthorizedOrganizationAccessException
     */
    public function assertUserBelongsToOrganization(User $user, int $organizationId): void
    {
        $organization = $this->organizationRepository->findById($organizationId);

        if (!$organization) {
            throw new OrganizationNotFoundException();
        }

        $isOwner  = $organization->user_id === $user->id;
        $isMember = $this->organizationUserRepository->findByUserAndOrganization($user->id, $organizationId) !== null;

        if (!$isOwner && !$isMember) {
            throw new UnauthorizedOrganizationAccessException();
        }
    }
}
