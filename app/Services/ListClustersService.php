<?php

namespace App\Services;

use App\Repositories\ClusterRepository;
use Illuminate\Support\Collection;

class ListClustersService
{
    public function __construct(private ClusterRepository $clusters)
    {
    }

    public function handle(string $experimentId): Collection
    {
        return $this->clusters->listByExperiment($experimentId);
    }
}
