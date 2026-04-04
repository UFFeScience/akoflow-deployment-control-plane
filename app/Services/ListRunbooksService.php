<?php

namespace App\Services;

use App\Models\EnvironmentTemplateProviderConfiguration;
use Illuminate\Database\Eloquent\Collection;

class ListRunbooksService
{
    public function handle(string $configId): Collection
    {
        $config = EnvironmentTemplateProviderConfiguration::with('runbooks.tasks')
            ->findOrFail($configId);

        return $config->runbooks->load('tasks');
    }
}
