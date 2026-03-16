<?php

namespace App\Repositories;

use App\Models\ExperimentTemplateTerraformModule;

class ExperimentTemplateTerraformModuleRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new ExperimentTemplateTerraformModule());
    }

    public function findAllByVersionId(string $versionId): \Illuminate\Database\Eloquent\Collection
    {
        return ExperimentTemplateTerraformModule::where('template_version_id', $versionId)
            ->orderBy('provider_type')
            ->get();
    }

    public function findByVersionAndProvider(string $versionId, string $providerType): ?ExperimentTemplateTerraformModule
    {
        return ExperimentTemplateTerraformModule::where('template_version_id', $versionId)
            ->where('provider_type', $providerType)
            ->first();
    }

    public function upsertForVersionAndProvider(string $versionId, string $providerType, array $data): ExperimentTemplateTerraformModule
    {
        $data['template_version_id'] = $versionId;
        $data['provider_type']       = $providerType;

        return ExperimentTemplateTerraformModule::updateOrCreate(
            ['template_version_id' => $versionId, 'provider_type' => $providerType],
            $data,
        );
    }
}
