<?php

namespace App\Services;

use App\Models\AnsiblePlaybook;
use App\Models\AnsiblePlaybookTask;
use Illuminate\Database\Eloquent\Collection;

class SyncAnsiblePlaybookTasksService
{
    public function handle(string $playbookId, array $tasks): Collection
    {
        $activity = AnsiblePlaybook::findOrFail($playbookId);

        $activity->tasks()->delete();

        foreach ($tasks as $i => $taskData) {
            AnsiblePlaybookTask::create(array_merge($taskData, [
                'ansible_playbook_id' => $activity->id,
                'position'            => $taskData['position'] ?? $i,
            ]));
        }

        return $activity->tasks()->get();
    }
}
