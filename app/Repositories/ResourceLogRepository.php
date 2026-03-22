<?php

namespace App\Repositories;

use App\Models\ResourceLog;

class ResourceLogRepository extends BaseRepository
{
    public function __construct(ResourceLog $model)
    {
        parent::__construct($model);
    }

    public function listByResource(string $resourceId)
    {
        return $this->model
            ->where('provisioned_resource_id', $resourceId)
            ->orderByDesc('created_at')
            ->get();
    }
}
