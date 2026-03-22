<?php

namespace App\Services;

use App\Repositories\ClusterRepository;
use Illuminate\Support\Collection;

class ListClustersService
{
    public function __construct(private ClusterRepository $deployments)
    {
    }

    public function handle(string $environmentId): Collection
    {
        return $this->deployments
            ->listByEnvironment($environmentId)
            ->load(['instanceGroups.instanceType']);
    }
}
