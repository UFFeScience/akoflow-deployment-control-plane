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

    public function findByConfigurationId(string $configId, string $phase = 'provision'): ?EnvironmentTemplateAnsiblePlaybook
    {
        return EnvironmentTemplateAnsiblePlaybook::where('provider_configuration_id', $configId)
            ->where('phase', $phase)
            ->first();
    }

    public function upsertForConfiguration(string $configId, array $data, string $phase = 'provision'): EnvironmentTemplateAnsiblePlaybook
    {
        $data['provider_configuration_id'] = $configId;
        $data['phase']                     = $phase;

        return EnvironmentTemplateAnsiblePlaybook::updateOrCreate(
            ['provider_configuration_id' => $configId, 'phase' => $phase],
            $data,
        );
    }
}
