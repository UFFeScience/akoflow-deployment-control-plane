<?php

namespace App\Services;

use App\Repositories\RunLogRepository;
use Illuminate\Support\Collection;

class ListRunLogsService
{
    public function __construct(private RunLogRepository $logs) {}

    public function handleByRun(string $runId, ?int $afterId = null): Collection
    {
        return $this->logs->listByRun($runId, $afterId);
    }

    public function handleByPlaybookRun(string $runId, ?int $afterId = null): Collection
    {
        return $this->logs->listByActivityRun($runId, $afterId);
    }

    public function handleByResource(string $resourceId, ?int $afterId = null): Collection
    {
        return $this->logs->listByResource($resourceId, $afterId);
    }
}
