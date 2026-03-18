<?php

namespace App\Services;

use App\Models\EnvironmentTemplateTerraformModule;
use App\Repositories\EnvironmentTemplateTerraformModuleRepository;
use Illuminate\Database\Eloquent\Collection;

class GetTemplateTerraformModuleService
{
    public function __construct(
        private EnvironmentTemplateTerraformModuleRepository $moduleRepository,
    ) {}

    /** Returns all modules for a version (one per provider). */
    public function allForVersion(string $versionId): Collection
    {
        return $this->moduleRepository->findAllByVersionId($versionId);
    }

    /** Returns the first module for a version (any provider), or null if absent. */
    public function firstForVersion(string $versionId): ?EnvironmentTemplateTerraformModule
    {
        return $this->moduleRepository->findFirstByVersion($versionId);
    }

    /** Returns a single module by version + providerType, or null if absent. */
    public function handle(string $versionId, string $providerType): ?EnvironmentTemplateTerraformModule
    {
        return $this->moduleRepository->findByVersionAndProvider($versionId, $providerType);
    }
}
