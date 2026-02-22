<?php

namespace App\Services;

use App\Repositories\ExperimentRepository;
use App\Models\Experiment;

class CreateExperimentService
{
    public function __construct(private ExperimentRepository $experiments)
    {
    }

    public function handle(string $projectId, array $data): Experiment
    {
        $data['project_id'] = $projectId;
        return $this->experiments->create($data);
    }
}
