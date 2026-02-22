<?php

namespace App\Services;

use App\Repositories\ExperimentRepository;
use App\Models\Experiment;

class ExperimentService
{
    public function __construct(protected ExperimentRepository $repo)
    {
    }

    public function listByProject(string $projectId)
    {
        return $this->repo->listByProject($projectId);
    }

    public function create(string $projectId, array $data): Experiment
    {
        $data['project_id'] = $projectId;
        return $this->repo->create($data);
    }
}
