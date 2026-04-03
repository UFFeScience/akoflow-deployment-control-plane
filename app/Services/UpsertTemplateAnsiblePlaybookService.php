<?php

namespace App\Services;

use App\Models\EnvironmentTemplateAnsiblePlaybook;
use App\Repositories\EnvironmentTemplateAnsiblePlaybookRepository;

class UpsertTemplateAnsiblePlaybookService
{
    public function __construct(
        private EnvironmentTemplateAnsiblePlaybookRepository $playbookRepository,
    ) {}

    public function handle(string $versionId, string $providerType, array $data): EnvironmentTemplateAnsiblePlaybook
    {
        unset($data['provider_type']);

        $config = \App\Models\EnvironmentTemplateProviderConfiguration::firstOrCreate(
            ['template_version_id' => $versionId, 'name' => strtoupper($providerType)],
            ['applies_to_providers' => [strtoupper($providerType)]],
        );

        return $this->playbookRepository->upsertForConfiguration($config->id, $data);
    }
}
