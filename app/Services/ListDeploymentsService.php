<?php

namespace App\Services;

use App\Repositories\DeploymentRepository;
use Illuminate\Support\Collection;

class ListDeploymentsService
{
    public function __construct(private DeploymentRepository $deployments)
    {
    }

    public function handle(string $environmentId): Collection
    {
        return $this->deployments->listByEnvironment($environmentId);
    }
}
