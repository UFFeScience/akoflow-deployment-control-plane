<?php

namespace App\Repositories;

use App\Models\InstanceGroup;

class InstanceGroupRepository extends BaseRepository
{
    public function __construct(InstanceGroup $model)
    {
        parent::__construct($model);
    }

    public function listByCluster(string $clusterId)
    {
        return $this->model->where('cluster_id', $clusterId)->get();
    }
}
