<?php

namespace App\Services;

use App\Exceptions\ProjectNotFoundException;
use App\Repositories\ProjectRepository;

class GetProjectByIdService
{
    public function __construct(private ProjectRepository $projectRepository)
    {}

    public function execute(int $projectId)
    {
        $project = $this->projectRepository->findById($projectId);

        if (!$project) {
            throw new ProjectNotFoundException();
        }

        return $project;
    }
}
