<?php

namespace App\Services;

use App\Repositories\ProjectRepository;

class DeleteProjectService
{
    public function __construct(private ProjectRepository $projectRepository)
    {}

    public function execute($project): bool
    {
        return $this->projectRepository->delete($project);
    }
}
