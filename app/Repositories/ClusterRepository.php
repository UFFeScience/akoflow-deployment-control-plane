<?php

namespace App\Repositories;

use App\Models\Cluster;

class ClusterRepository extends BaseRepository
{
    public function __construct(Cluster $model)
    {
        parent::__construct($model);
    }

    public function listByExperiment(string $experimentId)
    {
        return $this->model->where('experiment_id', $experimentId)->get();
    }
}
