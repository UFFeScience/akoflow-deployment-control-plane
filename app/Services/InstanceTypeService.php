<?php

namespace App\Services;

use App\Repositories\InstanceTypeRepository;
use App\Models\InstanceType;

class InstanceTypeService
{
    public function __construct(protected InstanceTypeRepository $repo)
    {
    }

    public function list(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repo->all();
    }

    public function create(array $data): InstanceType
    {
        return $this->repo->create($data);
    }

    public function updateStatus(string $id, string $status): ?InstanceType
    {
        return $this->repo->updateStatus($id, $status);
    }
}
