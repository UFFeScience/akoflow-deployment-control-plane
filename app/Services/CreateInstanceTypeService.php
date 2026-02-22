<?php

namespace App\Services;

use App\Repositories\InstanceTypeRepository;
use App\Models\InstanceType;

class CreateInstanceTypeService
{
    public function __construct(private InstanceTypeRepository $types)
    {
    }

    public function handle(array $data): InstanceType
    {
        return $this->types->create($data);
    }
}
