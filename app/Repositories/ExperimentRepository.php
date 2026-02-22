<?php

namespace App\Repositories;

use App\Models\Experiment;

class ExperimentRepository extends BaseRepository
{
    public function __construct(Experiment $model)
    {
        parent::__construct($model);
    }

    public function listByProject(string $projectId)
    {
        return $this->model->where('project_id', $projectId)->get();
    }
}
