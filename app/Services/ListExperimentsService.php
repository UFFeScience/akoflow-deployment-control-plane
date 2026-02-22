<?php

namespace App\Services;

use App\Repositories\ExperimentRepository;
use Illuminate\Support\Collection;

class ListExperimentsService
{
    public function __construct(private ExperimentRepository $experiments)
    {
    }

    public function handle(string $projectId): Collection
    {
        return $this->experiments->listByProject($projectId);
    }
}
