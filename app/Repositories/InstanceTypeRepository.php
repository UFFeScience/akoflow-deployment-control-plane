<?php

namespace App\Repositories;

use App\Models\InstanceType;

class InstanceTypeRepository extends BaseRepository
{
    public function __construct(InstanceType $model)
    {
        parent::__construct($model);
    }

    public function updateStatus(string $id, string $status)
    {
        return $this->update($id, ['status' => $status]);
    }
}
