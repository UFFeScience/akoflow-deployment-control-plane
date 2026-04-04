<?php

namespace App\Services;

use App\Models\AnsibleRun;
use App\Repositories\AnsibleRunRepository;

class GetAnsibleRunService
{
    public function __construct(private AnsibleRunRepository $repository) {}

    public function handle(string $runId): ?AnsibleRun
    {
        return $this->repository->find($runId);
    }
}
