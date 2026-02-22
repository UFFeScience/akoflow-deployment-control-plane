<?php

namespace App\Services;

use App\Repositories\ExperimentTemplateRepository;
use App\Models\ExperimentTemplate;

class CreateExperimentTemplateService
{
    public function __construct(private ExperimentTemplateRepository $templates)
    {
    }

    public function handle(array $data): ExperimentTemplate
    {
        return $this->templates->create($data);
    }
}
