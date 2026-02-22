<?php

namespace App\Services;

use App\Repositories\ExperimentTemplateVersionRepository;
use App\Models\ExperimentTemplateVersion;

class AddExperimentTemplateVersionService
{
    public function __construct(private ExperimentTemplateVersionRepository $versions)
    {
    }

    public function handle(string $templateId, array $data): ExperimentTemplateVersion
    {
        $data['template_id'] = $templateId;
        $version = $this->versions->create($data);

        if (!array_key_exists('is_active', $data) || $data['is_active']) {
            $this->versions->deactivateOtherVersions($templateId, $version->id);
        }

        return $version;
    }
}
