<?php

namespace App\Services;

use App\Models\EnvironmentTemplateTerraformModule;
use App\Models\EnvironmentTemplateProviderConfiguration;
use Illuminate\Database\Eloquent\Collection;

class GetTemplateTerraformModuleService
{
    public function allForVersion(string $versionId): Collection
    {
        return EnvironmentTemplateTerraformModule::whereHas('providerConfiguration', function ($q) use ($versionId) {
            $q->where('template_version_id', $versionId);
        })->get();
    }

    public function firstForVersion(string $versionId): ?EnvironmentTemplateTerraformModule
    {
        return $this->allForVersion($versionId)->first();
    }

    public function handle(string $versionId, string $providerType): ?EnvironmentTemplateTerraformModule
    {
        $upper = strtoupper($providerType);

        $specific = EnvironmentTemplateProviderConfiguration::where('template_version_id', $versionId)
            ->whereJsonContains('applies_to_providers', $upper)
            ->with('terraformModule')
            ->first();

        if ($specific?->terraformModule) {
            return $specific->terraformModule;
        }

        $default = EnvironmentTemplateProviderConfiguration::where('template_version_id', $versionId)
            ->whereJsonLength('applies_to_providers', 0)
            ->with('terraformModule')
            ->first();

        return $default?->terraformModule;
    }
}
