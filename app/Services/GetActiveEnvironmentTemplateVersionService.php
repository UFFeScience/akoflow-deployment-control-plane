<?php

namespace App\Services;

use App\Repositories\EnvironmentTemplateVersionRepository;
use App\Models\EnvironmentTemplateVersion;

class GetActiveEnvironmentTemplateVersionService
{
    public function __construct(
        private EnvironmentTemplateVersionRepository $versions,
    ) {}

    public function handle(string $templateId): ?EnvironmentTemplateVersion
    {
        return $this->versions->getActiveByTemplateId($templateId);
    }
}
