<?php

namespace App\Services;

use App\Models\EnvironmentTemplateAnsiblePlaybook;
use App\Models\EnvironmentTemplateProviderConfiguration;
use Illuminate\Database\Eloquent\Collection;

class GetTemplateAnsiblePlaybookService
{
    public function allForVersion(string $versionId): Collection
    {
        return EnvironmentTemplateAnsiblePlaybook::whereHas('providerConfiguration', function ($q) use ($versionId) {
            $q->where('template_version_id', $versionId);
        })->get();
    }

    public function firstForVersion(string $versionId): ?EnvironmentTemplateAnsiblePlaybook
    {
        return $this->allForVersion($versionId)->first();
    }

    public function handle(string $versionId, string $providerType): ?EnvironmentTemplateAnsiblePlaybook
    {
        $upper = strtoupper($providerType);

        $specific = EnvironmentTemplateProviderConfiguration::where('template_version_id', $versionId)
            ->whereJsonContains('applies_to_providers', $upper)
            ->with('ansiblePlaybook')
            ->first();

        if ($specific?->ansiblePlaybook) {
            return $specific->ansiblePlaybook;
        }

        $default = EnvironmentTemplateProviderConfiguration::where('template_version_id', $versionId)
            ->whereJsonLength('applies_to_providers', 0)
            ->orWhere('applies_to_providers', null)
            ->with('ansiblePlaybook')
            ->first();

        return $default?->ansiblePlaybook;
    }
}
