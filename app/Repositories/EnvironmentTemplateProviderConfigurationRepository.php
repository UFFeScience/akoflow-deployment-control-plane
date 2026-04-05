<?php

namespace App\Repositories;

use App\Models\EnvironmentTemplateProviderConfiguration;
use Illuminate\Database\Eloquent\Collection;

class EnvironmentTemplateProviderConfigurationRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new EnvironmentTemplateProviderConfiguration());
    }

    public function findAllByVersionId(string $versionId): Collection
    {
        return EnvironmentTemplateProviderConfiguration::where('template_version_id', $versionId)
            ->with(['terraformModule', 'ansiblePlaybook', 'teardownPlaybook', 'runbooks'])
            ->orderBy('id')
            ->get();
    }

    public function findByVersionAndId(string $versionId, string $configId): ?EnvironmentTemplateProviderConfiguration
    {
        return EnvironmentTemplateProviderConfiguration::where('template_version_id', $versionId)
            ->where('id', $configId)
            ->with(['terraformModule', 'ansiblePlaybook', 'teardownPlaybook', 'runbooks'])
            ->first();
    }

    public function createForVersion(string $versionId, array $data): EnvironmentTemplateProviderConfiguration
    {
        $data['template_version_id'] = $versionId;
        return EnvironmentTemplateProviderConfiguration::create($data);
    }

    public function updateConfig(string $configId, array $data): ?EnvironmentTemplateProviderConfiguration
    {
        $config = EnvironmentTemplateProviderConfiguration::find($configId);
        if (!$config) return null;
        $config->fill($data);
        $config->save();
        return $config->fresh(['terraformModule', 'ansiblePlaybook', 'teardownPlaybook']);
    }

    public function deleteConfig(string $configId): bool
    {
        $config = EnvironmentTemplateProviderConfiguration::find($configId);
        if (!$config) return false;
        return (bool) $config->delete();
    }
}
