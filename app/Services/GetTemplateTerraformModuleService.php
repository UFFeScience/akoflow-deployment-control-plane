<?php

namespace App\Services;

use App\Models\ExperimentTemplateTerraformModule;
use App\Repositories\ExperimentTemplateTerraformModuleRepository;
use Illuminate\Database\Eloquent\Collection;

class GetTemplateTerraformModuleService
{
    public function __construct(
        private ExperimentTemplateTerraformModuleRepository $moduleRepository,
    ) {}

    /** Returns all modules for a version (one per provider). */
    public function allForVersion(string $versionId): Collection
    {
        return $this->moduleRepository->findAllByVersionId($versionId);
    }

    /** Returns a single module by version + providerType, or null if absent. */
    public function handle(string $versionId, string $providerType): ?ExperimentTemplateTerraformModule
    {
        return $this->moduleRepository->findByVersionAndProvider($versionId, $providerType);
    }
}
