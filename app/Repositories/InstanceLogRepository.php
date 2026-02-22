<?php

namespace App\Repositories;

use App\Models\InstanceLog;

class InstanceLogRepository extends BaseRepository
{
    public function __construct(InstanceLog $model)
    {
        parent::__construct($model);
    }

    public function listByInstance(string $instanceId)
    {
        return $this->model
            ->where('provisioned_instance_id', $instanceId)
            ->orderByDesc('created_at')
            ->get();
    }
}
