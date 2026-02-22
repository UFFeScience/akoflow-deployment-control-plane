<?php

namespace App\Repositories;

use App\Models\ExperimentTemplateVersion;

class ExperimentTemplateVersionRepository extends BaseRepository
{
    public function __construct(ExperimentTemplateVersion $model)
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
}
