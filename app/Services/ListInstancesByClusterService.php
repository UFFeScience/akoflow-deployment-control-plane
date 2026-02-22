<?php

namespace App\Services;

use App\Repositories\ProvisionedInstanceRepository;
use Illuminate\Support\Collection;

class ListInstancesByClusterService
{
    public function __construct(private ProvisionedInstanceRepository $instances)
    {
    }

    public function handle(string $clusterId): Collection
    {
        return $this->instances->listByCluster($clusterId);
    }
}
