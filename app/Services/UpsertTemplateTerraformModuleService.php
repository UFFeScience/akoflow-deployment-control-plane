<?php

namespace App\Services;

use App\Models\EnvironmentTemplateTerraformModule;
use App\Repositories\EnvironmentTemplateTerraformModuleRepository;
use App\Repositories\EnvironmentTemplateVersionRepository;

class UpsertTemplateTerraformModuleService
{
    public function __construct(
        private EnvironmentTemplateTerraformModuleRepository $moduleRepository,
        private EnvironmentTemplateVersionRepository         $versionRepository,
    ) {}

    public function handle(string $versionId, string $providerType, array $data): EnvironmentTemplateTerraformModule
    {
        // provider_type is always the one from the route — ignore any body value
        unset($data['provider_type']);

        return $this->moduleRepository->upsertForVersionAndProvider($versionId, $providerType, $data);
    }

    // kept for slug-based auto-detection when module_slug arrives without provider_type context
    public function detectProviderTypeFromSlug(string $slug): string
    {
        if (str_starts_with($slug, 'aws')) {
            return 'aws';
        }

        if (str_starts_with($slug, 'gcp')) {
            return 'gcp';
        }

        if (str_starts_with($slug, 'azure')) {
            return 'azure';
        }

        return 'custom';
    }
}
