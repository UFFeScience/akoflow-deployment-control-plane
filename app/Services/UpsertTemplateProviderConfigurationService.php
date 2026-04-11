<?php

namespace App\Services;

use App\Models\EnvironmentTemplateProviderConfiguration;
use App\Repositories\EnvironmentTemplateProviderConfigurationRepository;
use App\Repositories\EnvironmentTemplateTerraformModuleRepository;

class UpsertTemplateProviderConfigurationService
{
    public function __construct(
        private EnvironmentTemplateProviderConfigurationRepository $configRepository,
        private EnvironmentTemplateTerraformModuleRepository       $terraformRepository,
    ) {}

    public function createConfig(string $versionId, array $data): EnvironmentTemplateProviderConfiguration
    {
        return $this->configRepository->createForVersion($versionId, [
            'name'                 => $data['name'],
            'applies_to_providers' => $data['applies_to_providers'] ?? [],
        ]);
    }

    public function updateConfig(string $configId, array $data): ?EnvironmentTemplateProviderConfiguration
    {
        return $this->configRepository->updateConfig($configId, [
            'name'                 => $data['name'],
            'applies_to_providers' => $data['applies_to_providers'] ?? [],
        ]);
    }

    public function deleteConfig(string $configId): bool
    {
        return $this->configRepository->deleteConfig($configId);
    }

    public function upsertTerraform(string $configId, array $data): void
    {
        unset($data['provider_configuration_id']);
        $this->terraformRepository->upsertForConfiguration($configId, $data);
    }
}
