<?php

namespace App\Services;

use App\Models\ExperimentTemplateVersion;
use App\Repositories\ExperimentTemplateVersionRepository;

class ActivateTemplateVersionService
{
    public function __construct(private ExperimentTemplateVersionRepository $versions) {}

    public function handle(string $templateId, string $versionId): ?ExperimentTemplateVersion
    {
        $version = ExperimentTemplateVersion::where('template_id', $templateId)->find($versionId);

        if (!$version) {
            return null;
        }

        $version->update(['is_active' => true]);
        $this->versions->deactivateOtherVersions($templateId, $version->id);

        return $version->fresh();
    }
}
