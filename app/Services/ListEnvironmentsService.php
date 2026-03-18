<?php

namespace App\Services;

use App\Repositories\EnvironmentRepository;
use Illuminate\Support\Collection;

class ListEnvironmentsService
{
    public function __construct(private EnvironmentRepository $environments)
    {
    }

    public function handle(string $projectId): Collection
    {
        return $this->environments->listByProject($projectId);
    }
}
