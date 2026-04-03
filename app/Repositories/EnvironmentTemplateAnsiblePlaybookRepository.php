<?php

namespace App\Repositories;

use App\Models\EnvironmentTemplateAnsiblePlaybook;
use Illuminate\Database\Eloquent\Collection;

class EnvironmentTemplateAnsiblePlaybookRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new EnvironmentTemplateAnsiblePlaybook());
    }

    public function findByConfigurationId(string $configId): ?EnvironmentTemplateAnsiblePlaybook
    {
        return EnvironmentTemplateAnsiblePlaybook::where('provider_configuration_id', $configId)->first();
    }

    public function upsertForConfiguration(string $configId, array $data): EnvironmentTemplateAnsiblePlaybook
    {
        $data['provider_configuration_id'] = $configId;

        return EnvironmentTemplateAnsiblePlaybook::updateOrCreate(
            ['provider_configuration_id' => $configId],
            $data,
        );
    }
}
