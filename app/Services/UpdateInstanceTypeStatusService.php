<?php

namespace App\Services;

use App\Repositories\InstanceTypeRepository;
use App\Models\InstanceType;

class UpdateInstanceTypeStatusService
{
    public function __construct(private InstanceTypeRepository $types)
    {
    }

    public function handle(string $id, string $status): ?InstanceType
    {
        return $this->types->update($id, ['status' => $status]);
    }
}
