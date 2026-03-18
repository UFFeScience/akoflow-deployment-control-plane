<?php

namespace App\Services;

use App\Models\EnvironmentTemplateVersion;
use App\Repositories\EnvironmentTemplateVersionRepository;

class ActivateTemplateVersionService
{
    public function __construct(private EnvironmentTemplateVersionRepository $versions) {}

    public function handle(string $templateId, string $versionId): ?EnvironmentTemplateVersion
    {
        $version = EnvironmentTemplateVersion::where('template_id', $templateId)->find($versionId);

        if (!$version) {
            return null;
        }

        $version->update(['is_active' => true]);
        $this->versions->deactivateOtherVersions($templateId, $version->id);

        return $version->fresh();
    }
}
