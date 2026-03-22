<?php

namespace App\Services;

use App\Exceptions\ClusterNotFoundException;
use App\Models\Deployment;
use App\Models\User;
use App\Repositories\ClusterRepository;

class ClusterAuthorizationService
{
    public function __construct(
        private ClusterRepository $clusterRepository,
        private EnvironmentAuthorizationService $environmentAuthorizationService,
    ) {}

    /**
     * Fetch a deployment by id and assert the user has access to its
     * environment's project's organization.
     *
     * @throws ClusterNotFoundException
     * @throws \App\Exceptions\EnvironmentNotFoundException
     * @throws \App\Exceptions\OrganizationNotFoundException
     * @throws \App\Exceptions\UnauthorizedOrganizationAccessException
     */
    public function assertUserCanAccessCluster(User $user, string $clusterId): Deployment
    {
        /** @var Deployment|null $deployment */
        $deployment = $this->clusterRepository->find($clusterId);

        if (!$deployment) {
            throw new ClusterNotFoundException();
        }

        $this->environmentAuthorizationService->assertUserCanAccessEnvironment($user, (string) $deployment->environment_id);

        return $deployment;
    }
}
