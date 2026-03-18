<?php

namespace App\Repositories;

use App\Models\EnvironmentTemplateTerraformModule;

class EnvironmentTemplateTerraformModuleRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new EnvironmentTemplateTerraformModule());
    }

    public function findAllByVersionId(string $versionId): \Illuminate\Database\Eloquent\Collection
    {
        return EnvironmentTemplateTerraformModule::where('template_version_id', $versionId)
            ->orderBy('provider_type')
            ->get();
    }

    public function findByVersionAndProvider(string $versionId, string $providerType): ?EnvironmentTemplateTerraformModule
    {
        return EnvironmentTemplateTerraformModule::where('template_version_id', $versionId)
            ->where('provider_type', $providerType)
            ->first();
    }

    public function findFirstByVersion(string $versionId): ?EnvironmentTemplateTerraformModule
    {
        return EnvironmentTemplateTerraformModule::where('template_version_id', $versionId)
            ->orderBy('provider_type')
            ->first();
    }

    public function upsertForVersionAndProvider(string $versionId, string $providerType, array $data): EnvironmentTemplateTerraformModule
    {
        $data['template_version_id'] = $versionId;
        $data['provider_type']       = $providerType;

        return EnvironmentTemplateTerraformModule::updateOrCreate(
            ['template_version_id' => $versionId, 'provider_type' => $providerType],
            $data,
        );
    }
}
