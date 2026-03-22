<?php

namespace App\Services;

use App\Repositories\DeploymentRepository;

class DeleteDeploymentService
{
    public function __construct(private DeploymentRepository $deployments)
    {
    }

    public function handle(string $id): bool
    {
        return $this->deployments->delete($id);
    }
}
