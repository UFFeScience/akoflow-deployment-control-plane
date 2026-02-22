<?php

namespace App\Services;

use App\Repositories\InstanceLogRepository;
use Illuminate\Support\Collection;

class ListInstanceLogsService
{
    public function __construct(private InstanceLogRepository $logs)
    {
    }

    public function handle(string $instanceId): Collection
    {
        return $this->logs->listByInstance($instanceId);
    }
}
