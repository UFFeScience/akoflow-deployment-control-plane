<?php

namespace App\Services;

use App\Models\AnsiblePlaybookTask;
use App\Models\EnvironmentTemplateRunbook;

class CreateRunbookService
{
    public function handle(string $configId, array $data): EnvironmentTemplateRunbook
    {
        $tasks = $data['tasks'] ?? [];
        unset($data['tasks']);

        $runbook = EnvironmentTemplateRunbook::create(array_merge($data, [
            'provider_configuration_id' => $configId,
        ]));

        foreach ($tasks as $i => $taskData) {
            AnsiblePlaybookTask::create(array_merge($taskData, [
                'runbook_id' => $runbook->id,
                'position'   => $taskData['position'] ?? $i,
            ]));
        }

        return $runbook->load('tasks');
    }
}
