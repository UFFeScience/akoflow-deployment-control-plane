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
        private ExperimentAuthorizationService $experimentAuthorizationService,
    ) {}

    /**
     * Fetch a cluster by id and assert the user has access to its
     * experiment's project's organization.
     *
     * @throws ClusterNotFoundException
     * @throws \App\Exceptions\ExperimentNotFoundException
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

        $this->experimentAuthorizationService->assertUserCanAccessExperiment($user, (string) $cluster->experiment_id);

        return $cluster;
    }
}
