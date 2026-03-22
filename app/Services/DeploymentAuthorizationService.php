<?php

namespace App\Services;

use App\Exceptions\DeploymentNotFoundException;
use App\Models\Deployment;
use App\Models\User;
use App\Repositories\DeploymentRepository;

class DeploymentAuthorizationService
{
    public function __construct(
        private DeploymentRepository $deploymentRepository,
        private EnvironmentAuthorizationService $environmentAuthorizationService,
    ) {}

    /**
     * Fetch a deployment by id and assert the user has access to its
     * environment's project's organization.
     *
     * @throws DeploymentNotFoundException
     * @throws \App\Exceptions\EnvironmentNotFoundException
     * @throws \App\Exceptions\OrganizationNotFoundException
     * @throws \App\Exceptions\UnauthorizedOrganizationAccessException
     */
    public function assertUserCanAccessDeployment(User $user, string $deploymentId): Deployment
    {
        /** @var Deployment|null $deployment */
        $deployment = $this->deploymentRepository->find($deploymentId);

        if (!$deployment) {
            throw new DeploymentNotFoundException();
        }

        $this->environmentAuthorizationService->assertUserCanAccessEnvironment($user, (string) $deployment->environment_id);

        return $deployment;
    }
}
