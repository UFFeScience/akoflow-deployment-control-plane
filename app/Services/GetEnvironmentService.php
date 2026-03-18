<?php

namespace App\Services;

use App\Models\Environment;
use App\Repositories\EnvironmentRepository;

class GetEnvironmentService
{
    public function __construct(private EnvironmentRepository $environments)
    {
    }

    public function handle(string $projectId, string $environmentId): ?Environment
    {
        return $this->environments->findByProject($projectId, $environmentId);
    }
}
