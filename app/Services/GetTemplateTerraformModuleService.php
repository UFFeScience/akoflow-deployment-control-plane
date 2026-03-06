<?php

namespace App\Services;

use App\Models\ExperimentTemplateTerraformModule;
use App\Repositories\ExperimentTemplateTerraformModuleRepository;

class GetTemplateTerraformModuleService
{
    public function __construct(
        private ExperimentTemplateTerraformModuleRepository $moduleRepository,
    ) {}

    public function handle(string $versionId): ?ExperimentTemplateTerraformModule
    {
        return $this->moduleRepository->findByVersionId($versionId);
    }
}
