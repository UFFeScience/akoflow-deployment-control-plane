<?php

namespace App\Services;

use App\Exceptions\EnvironmentNotFoundException;
use App\Models\Environment;
use App\Models\User;
use App\Repositories\EnvironmentRepository;

class EnvironmentAuthorizationService
{
    public function __construct(
        private EnvironmentRepository $environmentRepository,
        private OrganizationAuthorizationService $organizationAuthorizationService,
    ) {}

    /**
     * Fetch an environment by its id and assert the user belongs to the
     * environment's project's organization.
     *
     * @throws EnvironmentNotFoundException
     * @throws \App\Exceptions\OrganizationNotFoundException
     * @throws \App\Exceptions\UnauthorizedOrganizationAccessException
     */
    public function assertUserCanAccessEnvironment(User $user, string $environmentId): Environment
    {
        /** @var Environment|null $environment */
        $environment = $this->environmentRepository->find($environmentId);

        if (!$environment) {
            throw new EnvironmentNotFoundException();
        }

        $project = $environment->project()->first();

        $this->organizationAuthorizationService->assertUserBelongsToOrganization($user, $project->organization_id);

        return $environment;
    }
}
