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

        // Accept frontend-sent template version ID (snake_case from API payload)
        // The field can arrive as 'experiment_template_version_id' (already correct) or
        // as 'template_version_id' (legacy alias from the old wizard)
        if (!isset($data['experiment_template_version_id']) && isset($data['template_version_id'])) {
            $data['experiment_template_version_id'] = $data['template_version_id'];
        }
        unset($data['template_version_id']);

        return $this->experiments->create($data);
    }
}
