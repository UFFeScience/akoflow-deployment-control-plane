<?php

namespace App\Services;

use App\Repositories\ExperimentTemplateVersionRepository;
use App\Models\ExperimentTemplateVersion;

class GetActiveExperimentTemplateVersionService
{
    public function __construct(
        private ExperimentTemplateVersionRepository $versions,
    ) {}

    public function handle(string $templateId): ?ExperimentTemplateVersion
    {
        return $this->versions->getActiveByTemplateId($templateId);
    }
}
