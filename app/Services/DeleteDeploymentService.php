<?php

namespace App\Services;

use App\Repositories\ClusterRepository;

class DeleteClusterService
{
    public function __construct(private ClusterRepository $deployments)
    {
    }

    public function handle(string $id): bool
    {
        return $this->deployments->delete($id);
    }
}
