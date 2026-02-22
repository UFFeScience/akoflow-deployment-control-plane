<?php

namespace App\Services;

use App\Models\Experiment;
use App\Repositories\ExperimentRepository;

class GetExperimentService
{
    public function __construct(private ExperimentRepository $experiments)
    {
    }

    public function handle(string $projectId, string $experimentId): ?Experiment
    {
        return $this->experiments->findByProject($projectId, $experimentId);
    }
}
