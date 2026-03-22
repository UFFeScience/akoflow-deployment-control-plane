<?php

namespace App\Services;

use App\Repositories\ResourceLogRepository;
use Illuminate\Support\Collection;

class ListResourceLogsService
{
    public function __construct(private ResourceLogRepository $logs) {}

    public function handle(string $resourceId): Collection
    {
        return $this->logs->listByResource($resourceId);
    }
}
