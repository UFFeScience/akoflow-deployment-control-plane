<?php

namespace App\Services;

use App\Models\EnvironmentTemplateProviderConfiguration;
use Illuminate\Database\Eloquent\Collection;

class ListAnsiblePlaybooksService
{
    public function handle(string $configId): Collection
    {
        $config = EnvironmentTemplateProviderConfiguration::with('playbooks.tasks')
            ->findOrFail($configId);

        return $config->playbooks->load('tasks');
    }
}
