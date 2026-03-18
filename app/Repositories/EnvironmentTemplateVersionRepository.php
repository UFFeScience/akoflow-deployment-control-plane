<?php

namespace App\Repositories;

use App\Models\EnvironmentTemplateVersion;

class EnvironmentTemplateVersionRepository extends BaseRepository
{
    public function __construct(EnvironmentTemplateVersion $model)
    {
        parent::__construct($model);
    }

    public function deactivateOtherVersions(string $templateId, string $exceptId): void
    {
        $this->model
            ->where('template_id', $templateId)
            ->where('id', '!=', $exceptId)
            ->update(['is_active' => false]);
    }

    public function getActiveByTemplateId(string $templateId): ?EnvironmentTemplateVersion
    {
        return $this->model
            ->where('template_id', $templateId)
            ->where('is_active', true)
            ->latest('created_at')
            ->first();
    }
}
