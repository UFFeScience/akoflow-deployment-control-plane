<?php

namespace App\Repositories;

use App\Models\Deployment;

class ClusterRepository extends BaseRepository
{
    public function __construct(Deployment $model)
    {
        parent::__construct($model);
    }

    public function listByEnvironment(string $environmentId)
    {
        return $this->model->where('environment_id', $environmentId)->get();
    }
}
