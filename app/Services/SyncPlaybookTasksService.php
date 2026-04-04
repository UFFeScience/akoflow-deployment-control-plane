<?php

namespace App\Services;

use App\Models\AnsiblePlaybookTask;
use App\Models\EnvironmentTemplateProviderConfiguration;
use Illuminate\Database\Eloquent\Collection;

class SyncPlaybookTasksService
{
    public function handle(string $configId, array $tasks): ?Collection
    {
        $config   = EnvironmentTemplateProviderConfiguration::with('ansiblePlaybook')->findOrFail($configId);
        $playbook = $config->ansiblePlaybook;

        if (!$playbook) {
            return null;
        }

        $playbook->tasks()->delete();

        foreach ($tasks as $i => $taskData) {
            AnsiblePlaybookTask::create(array_merge($taskData, [
                'ansible_playbook_id' => $playbook->id,
                'position'            => $taskData['position'] ?? $i,
            ]));
        }

        return $playbook->fresh()->tasks;
    }
}
