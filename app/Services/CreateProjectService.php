<?php

namespace App\Services;

use App\Repositories\ProjectRepository;

class CreateProjectService
{
    public function __construct(private ProjectRepository $projectRepository)
    {}

    public function execute(int $organizationId, array $data)
    {
        return $this->projectRepository->create([
            'organization_id' => $organizationId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
    }
}
