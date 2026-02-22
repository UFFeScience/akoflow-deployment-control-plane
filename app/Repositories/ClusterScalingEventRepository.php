<?php

namespace App\Repositories;

use App\Models\ClusterScalingEvent;

class ClusterScalingEventRepository extends BaseRepository
{
    public function __construct(ClusterScalingEvent $model)
    {
        parent::__construct($model);
    }

    public function listByCluster(string $clusterId)
    {
        return $this->model
            ->where('cluster_id', $clusterId)
            ->orderByDesc('created_at')
            ->get();
    }
}
