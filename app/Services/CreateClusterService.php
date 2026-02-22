<?php

namespace App\Services;

use App\Repositories\ClusterRepository;
use App\Models\Cluster;

class CreateClusterService
{
    public function __construct(private ClusterRepository $clusters)
    {
    }

    public function handle(string $experimentId, array $data): Cluster
    {
        $data['experiment_id'] = $experimentId;
        return $this->clusters->create($data);
    }
}
