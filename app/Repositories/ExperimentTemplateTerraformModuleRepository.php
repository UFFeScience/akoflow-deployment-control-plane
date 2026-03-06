<?php

namespace App\Repositories;

use App\Models\ExperimentTemplateTerraformModule;

class ExperimentTemplateTerraformModuleRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new ExperimentTemplateTerraformModule());
    }

    public function findByVersionId(string $versionId): ?ExperimentTemplateTerraformModule
    {
        return ExperimentTemplateTerraformModule::where('template_version_id', $versionId)->first();
    }

    public function upsertForVersion(string $versionId, array $data): ExperimentTemplateTerraformModule
    {
        $data['template_version_id'] = $versionId;

        return ExperimentTemplateTerraformModule::updateOrCreate(
            ['template_version_id' => $versionId],
            $data,
        );
    }
}
