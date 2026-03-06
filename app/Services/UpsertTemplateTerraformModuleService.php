<?php

namespace App\Services;

use App\Models\ExperimentTemplateTerraformModule;
use App\Repositories\ExperimentTemplateTerraformModuleRepository;
use App\Repositories\ExperimentTemplateVersionRepository;

class UpsertTemplateTerraformModuleService
{
    public function __construct(
        private ExperimentTemplateTerraformModuleRepository $moduleRepository,
        private ExperimentTemplateVersionRepository         $versionRepository,
    ) {}

    public function handle(string $versionId, array $data): ExperimentTemplateTerraformModule
    {
        // Derive provider_type from module_slug when not explicitly provided
        if (empty($data['provider_type']) && !empty($data['module_slug'])) {
            $data['provider_type'] = $this->detectProviderTypeFromSlug($data['module_slug']);
        }

        return $this->moduleRepository->upsertForVersion($versionId, $data);
    }

    private function detectProviderTypeFromSlug(string $slug): string
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
