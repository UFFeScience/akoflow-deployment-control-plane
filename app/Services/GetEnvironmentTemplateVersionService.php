<?php

namespace App\Services;

use App\Models\EnvironmentTemplateVersion;
use App\Repositories\EnvironmentTemplateVersionRepository;

class GetEnvironmentTemplateVersionService
{
    public function __construct(private EnvironmentTemplateVersionRepository $versions) {}

    public function handle(string $templateId, string $versionId): ?EnvironmentTemplateVersion
    {
        return EnvironmentTemplateVersion::where('template_id', $templateId)
            ->with(['providerConfigurations.terraformModule', 'providerConfigurations.playbooks'])
            ->find($versionId);
    }

    public function findById(string $versionId): ?EnvironmentTemplateVersion
    {
        return EnvironmentTemplateVersion::with(['providerConfigurations.terraformModule', 'providerConfigurations.playbooks'])
            ->find($versionId);
    }
}
