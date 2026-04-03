<?php

namespace App\Repositories;

use App\Models\EnvironmentTemplateTerraformModule;
use Illuminate\Database\Eloquent\Collection;

class EnvironmentTemplateTerraformModuleRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new EnvironmentTemplateTerraformModule());
    }

    public function findByConfigurationId(string $configId): ?EnvironmentTemplateTerraformModule
    {
        return EnvironmentTemplateTerraformModule::where('provider_configuration_id', $configId)->first();
    }

    public function upsertForConfiguration(string $configId, array $data): EnvironmentTemplateTerraformModule
    {
        $data['provider_configuration_id'] = $configId;

        return EnvironmentTemplateTerraformModule::updateOrCreate(
            ['provider_configuration_id' => $configId],
            $data,
        );
    }
}
