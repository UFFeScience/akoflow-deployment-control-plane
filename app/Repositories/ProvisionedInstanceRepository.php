<?php

namespace App\Repositories;

use App\Models\ProvisionedInstance;

class ProvisionedInstanceRepository extends BaseRepository
{
    public function __construct(ProvisionedInstance $model)
    {
        parent::__construct($model);
    }

    public function listByCluster(string $clusterId)
    {
        return $this->model->where('cluster_id', $clusterId)->get();
    }
}
