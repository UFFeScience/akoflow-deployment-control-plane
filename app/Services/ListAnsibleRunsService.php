<?php

namespace App\Services;

use App\Models\AnsibleRun;
use App\Models\Deployment;
use Illuminate\Database\Eloquent\Collection;

class ListAnsibleRunsService
{
    public function handle(string $environmentId): Collection
    {
        $deploymentIds = Deployment::where('environment_id', $environmentId)->pluck('id');

        return AnsibleRun::whereIn('deployment_id', $deploymentIds)
            ->orderByDesc('created_at')
            ->get();
    }
}
