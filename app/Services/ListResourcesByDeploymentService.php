<?php

namespace App\Services;

use App\Repositories\ProvisionedResourceRepository;
use Illuminate\Support\Collection;

class ListResourcesByDeploymentService
{
    public function __construct(private ProvisionedResourceRepository $resources) {}

    public function handle(string $deploymentId): Collection
    {
        return $this->resources->listByDeployment($deploymentId);
    }
}
