<?php

namespace App\Repositories;

use App\Models\ProvisionedResource;

class ProvisionedResourceRepository extends BaseRepository
{
    public function __construct(ProvisionedResource $model)
    {
        parent::__construct($model);
    }

    public function listByDeployment(string $deploymentId)
    {
        return $this->model
            ->with('resourceType.kind')
            ->where('deployment_id', $deploymentId)
            ->get();
    }
}
