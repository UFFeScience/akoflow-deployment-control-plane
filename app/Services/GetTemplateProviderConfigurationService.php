<?php

namespace App\Services;

use App\Models\EnvironmentTemplateProviderConfiguration;
use App\Repositories\EnvironmentTemplateProviderConfigurationRepository;
use Illuminate\Database\Eloquent\Collection;

class GetTemplateProviderConfigurationService
{
    public function __construct(
        private EnvironmentTemplateProviderConfigurationRepository $configRepository,
    ) {}

    public function allForVersion(string $versionId): Collection
    {
        return $this->configRepository->findAllByVersionId($versionId);
    }

    public function findByVersionAndId(string $versionId, string $configId): ?EnvironmentTemplateProviderConfiguration
    {
        return $this->configRepository->findByVersionAndId($versionId, $configId);
    }
}
