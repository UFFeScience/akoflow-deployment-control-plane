<?php

namespace App\Services;

use App\Models\AnsiblePlaybookTask;
use App\Models\EnvironmentTemplateRunbook;
use Illuminate\Database\Eloquent\Collection;

class SyncRunbookTasksService
{
    public function handle(string $runbookId, array $tasks): Collection
    {
        $runbook = EnvironmentTemplateRunbook::findOrFail($runbookId);
        $runbook->tasks()->delete();

        foreach ($tasks as $i => $taskData) {
            AnsiblePlaybookTask::create(array_merge($taskData, [
                'runbook_id' => $runbook->id,
                'position'   => $taskData['position'] ?? $i,
            ]));
        }

        return $runbook->fresh()->tasks;
    }
}
