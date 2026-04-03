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
        unset($data['provider_type']);

        $config = \App\Models\EnvironmentTemplateProviderConfiguration::firstOrCreate(
            ['template_version_id' => $versionId, 'name' => strtoupper($providerType)],
            ['applies_to_providers' => [strtoupper($providerType)]],
        );

        return $this->moduleRepository->upsertForConfiguration($config->id, $data);
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
