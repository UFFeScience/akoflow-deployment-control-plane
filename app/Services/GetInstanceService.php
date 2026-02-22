<?php

namespace App\Services;

use App\Repositories\ProvisionedInstanceRepository;
use App\Models\ProvisionedInstance;

class GetInstanceService
{
    public function __construct(private ProvisionedInstanceRepository $instances)
    {
    }

    public function handle(string $id): ?ProvisionedInstance
    {
        return $this->instances->find($id);
    }
}
