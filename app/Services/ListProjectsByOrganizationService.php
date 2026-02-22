<?php

namespace App\Services;

use App\Repositories\ProjectRepository;

class ListProjectsByOrganizationService
{
    public function __construct(private ProjectRepository $projectRepository)
    {}

    public function execute(int $organizationId)
    {
        return $this->projectRepository->getByOrganizationId($organizationId);
    }
}
