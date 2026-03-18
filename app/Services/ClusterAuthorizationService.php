<?php

namespace App\Services;

use App\Exceptions\ClusterNotFoundException;
use App\Models\Cluster;
use App\Models\User;
use App\Repositories\ClusterRepository;

class ClusterAuthorizationService
{
    public function __construct(
        private ClusterRepository $clusterRepository,
        private EnvironmentAuthorizationService $environmentAuthorizationService,
    ) {}

    /**
     * Fetch a cluster by id and assert the user has access to its
     * environment's project's organization.
     *
     * @throws ClusterNotFoundException
     * @throws \App\Exceptions\EnvironmentNotFoundException
     * @throws \App\Exceptions\OrganizationNotFoundException
     * @throws \App\Exceptions\UnauthorizedOrganizationAccessException
     */
    public function assertUserCanAccessCluster(User $user, string $clusterId): Cluster
    {
        /** @var Cluster|null $cluster */
        $cluster = $this->clusterRepository->find($clusterId);

        if (!$cluster) {
            throw new ClusterNotFoundException();
        }

        $this->environmentAuthorizationService->assertUserCanAccessEnvironment($user, (string) $cluster->environment_id);

        return $cluster;
    }
}
