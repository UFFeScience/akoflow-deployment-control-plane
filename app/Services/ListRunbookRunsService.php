<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\RunbookRun;
use Illuminate\Database\Eloquent\Collection;

class ListRunbookRunsService
{
    public function handle(string $environmentId): Collection
    {
        $deploymentIds = Deployment::where('environment_id', $environmentId)->pluck('id');

        return RunbookRun::with('taskRuns')
            ->whereIn('deployment_id', $deploymentIds)
            ->orderByDesc('created_at')
            ->get();
    }
}
