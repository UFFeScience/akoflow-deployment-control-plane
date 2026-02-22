<?php

namespace App\Services;

use App\Repositories\ProjectRepository;

class UpdateProjectService
{
    public function __construct(private ProjectRepository $projectRepository)
    {}

    public function execute($project, array $data)
    {
        return $this->projectRepository->update($project, [
            'name' => $data['name'] ?? $project->name,
            'description' => $data['description'] ?? $project->description,
        ]);
    }
}
