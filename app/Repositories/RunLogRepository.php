<?php

namespace App\Repositories;

use App\Models\AnsibleRun;
use App\Models\RunLog;
use App\Models\TerraformRun;
use Illuminate\Support\Collection;

class RunLogRepository extends BaseRepository
{
    public function __construct(RunLog $model)
    {
        parent::__construct($model);
    }

    /**
     * All logs for a terraform run, optionally only rows after $afterId.
     */
    public function listByRun(string $runId, ?int $afterId = null): Collection
    {
        return $this->model
            ->where('terraform_run_id', $runId)
            ->when($afterId !== null, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->get();
    }

    /**
     * All logs for a provisioned resource, optionally only rows after $afterId.
     */
    public function listByResource(string $resourceId, ?int $afterId = null): Collection
    {
        return $this->model
            ->where('provisioned_resource_id', $resourceId)
            ->when($afterId !== null, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->get();
    }

    /**
     * Append one log line for a TerraformRun.
     */
    public function createForRun(TerraformRun $run, string $level, string $message): RunLog
    {
        return $this->model->create([
            'terraform_run_id' => $run->id,
            'environment_id'   => $run->environment_id,
            'source'           => RunLog::SOURCE_TERRAFORM,
            'level'            => $level,
            'message'          => $message,
        ]);
    }

    /**
     * Append one log line for an AnsibleRun.
     */
    public function createForAnsibleRun(AnsibleRun $run, string $level, string $message): RunLog
    {
        return $this->model->create([
            'ansible_run_id' => $run->id,
            'environment_id' => $run->deployment->environment_id ?? null,
            'source'         => RunLog::SOURCE_ANSIBLE,
            'level'          => $level,
            'message'        => $message,
        ]);
    }

    /**
     * All logs for an ansible run, optionally only rows after $afterId.
     */
    public function listByAnsibleRun(string $runId, ?int $afterId = null): Collection
    {
        return $this->model
            ->where('ansible_run_id', $runId)
            ->when($afterId !== null, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->get();
    }
}
