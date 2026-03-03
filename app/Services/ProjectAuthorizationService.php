<?php

namespace App\Services;

use App\Exceptions\ProjectNotFoundException;
use App\Exceptions\UnauthorizedOrganizationAccessException;
use App\Exceptions\UnauthorizedProjectAccessException;
use App\Models\Project;
use App\Models\User;
use App\Repositories\ProjectRepository;

class ProjectAuthorizationService
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private OrganizationAuthorizationService $organizationAuthorizationService,
    ) {}

    /**
     * Fetch a project by id and assert:
     *  1. The project exists.
     *  2. The project belongs to the given organization.
     *  3. The authenticated user belongs to that organization.
     *
     * @throws ProjectNotFoundException
     * @throws UnauthorizedProjectAccessException
     */
    public function assertUserCanAccessProject(User $user, int $organizationId, int $projectId): Project
    {
        $project = $this->projectRepository->findById($projectId);

        if (!$project) {
            throw new ProjectNotFoundException();
        }

        if ($project->organization_id !== $organizationId) {
            throw new UnauthorizedProjectAccessException();
        }

        $this->organizationAuthorizationService->assertUserBelongsToOrganization($user, $organizationId);

        return $project;
    }

    /**
     * Fetch a project by id (without knowing organizationId up-front) and
     * assert the user belongs to the project's organization.
     *
     * @throws ProjectNotFoundException
     * @throws UnauthorizedOrganizationAccessException
     */
    public function assertUserCanAccessProjectById(User $user, int $projectId): Project
    {
        $project = $this->projectRepository->findById($projectId);

        if (!$project) {
            throw new ProjectNotFoundException();
        }

        $this->organizationAuthorizationService->assertUserBelongsToOrganization($user, $project->organization_id);

        return $project;
    }
}
