<?php

namespace App\Services;

use App\Models\AnsiblePlaybookRun;
use App\Models\Deployment;
use Illuminate\Database\Eloquent\Collection;

class ListAnsiblePlaybookRunsService
{
    public function handleByEnvironment(string $environmentId): Collection
    {
        $deploymentIds = Deployment::where('environment_id', $environmentId)
            ->pluck('id');

        return AnsiblePlaybookRun::with('taskHostStatuses')
            ->whereIn('deployment_id', $deploymentIds)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function handleByDeployment(string $deploymentId): Collection
    {
        return AnsiblePlaybookRun::with('taskHostStatuses')
            ->where('deployment_id', $deploymentId)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();
    }
}
