<?php

namespace App\Repositories;

use App\Models\Cluster;

class ClusterRepository extends BaseRepository
{
    public function __construct(Cluster $model)
    {
        parent::__construct($model);
    }

    public function listByEnvironment(string $environmentId)
    {
        return $this->model->where('environment_id', $environmentId)->get();
    }
}
