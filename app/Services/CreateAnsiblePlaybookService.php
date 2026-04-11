<?php

namespace App\Services;

use App\Models\AnsiblePlaybook;
use App\Models\AnsiblePlaybookTask;

class CreateAnsiblePlaybookService
{
    public function handle(string $configId, array $data): AnsiblePlaybook
    {
        $tasks = $data['tasks'] ?? [];
        unset($data['tasks']);

        $activity = AnsiblePlaybook::create(array_merge($data, [
            'provider_configuration_id' => $configId,
        ]));

        foreach ($tasks as $i => $taskData) {
            AnsiblePlaybookTask::create(array_merge($taskData, [
                'ansible_playbook_id' => $activity->id,
                'position'            => $taskData['position'] ?? $i,
            ]));
        }

        return $activity->load('tasks');
    }
}
