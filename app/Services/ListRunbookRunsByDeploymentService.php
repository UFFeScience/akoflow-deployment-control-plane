<?php

namespace App\Services;

use App\Repositories\RunbookRunRepository;
use Illuminate\Database\Eloquent\Collection;

class ListRunbookRunsByDeploymentService
{
    public function __construct(private RunbookRunRepository $repository) {}

    public function handle(string $deploymentId): Collection
    {
        return $this->repository->findByDeployment($deploymentId)->load('taskRuns');
    }
}
