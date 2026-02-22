<?php

namespace App\Services;

use App\Repositories\ClusterRepository;

class DeleteClusterService
{
    public function __construct(private ClusterRepository $clusters)
    {
    }

    public function handle(string $id): bool
    {
        return $this->clusters->delete($id);
    }
}
