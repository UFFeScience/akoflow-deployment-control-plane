<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Services\CreateProjectService;
use App\Services\DeleteProjectService;
use App\Services\GetProjectByIdService;
use App\Services\ListProjectsByOrganizationService;
use App\Services\OrganizationAuthorizationService;
use App\Services\ProjectAuthorizationService;
use App\Services\UpdateProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        private CreateProjectService $createProjectService,
        private UpdateProjectService $updateProjectService,
        private DeleteProjectService $deleteProjectService,
        private ListProjectsByOrganizationService $listProjectsByOrganizationService,
        private GetProjectByIdService $getProjectByIdService,
        private OrganizationAuthorizationService $organizationAuthorizationService,
        private ProjectAuthorizationService $projectAuthorizationService,
    ) {}

    public function create(CreateProjectRequest $request, int $organizationId): JsonResponse
    {
        $this->organizationAuthorizationService->assertUserBelongsToOrganization($request->user(), $organizationId);

        $project = $this->createProjectService->execute($organizationId, $request->validated());

        return response()->json([
            'message' => 'Project created successfully',
            'data' => new ProjectResource($project),
        ], 201);
    }

    public function listByOrganization(Request $request, int $organizationId): JsonResponse
    {
        $this->organizationAuthorizationService->assertUserBelongsToOrganization($request->user(), $organizationId);

        $projects = $this->listProjectsByOrganizationService->execute($organizationId);

        return response()->json([
            'message' => 'Projects retrieved successfully',
            'data' => ProjectResource::collection($projects),
        ]);
    }

    public function getById(Request $request, int $organizationId, int $projectId): JsonResponse
    {
        $project = $this->projectAuthorizationService->assertUserCanAccessProject($request->user(), $organizationId, $projectId);

        return response()->json([
            'message' => 'Project retrieved successfully',
            'data' => new ProjectResource($project),
        ]);
    }

    public function update(UpdateProjectRequest $request, int $organizationId, int $projectId): JsonResponse
    {
        $project = $this->projectAuthorizationService->assertUserCanAccessProject($request->user(), $organizationId, $projectId);

        $updated = $this->updateProjectService->execute($project, $request->validated());

        return response()->json([
            'message' => 'Project updated successfully',
            'data' => new ProjectResource($updated),
        ]);
    }

    public function delete(Request $request, int $organizationId, int $projectId): JsonResponse
    {
        $project = $this->projectAuthorizationService->assertUserCanAccessProject($request->user(), $organizationId, $projectId);

        $this->deleteProjectService->execute($project);

        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }
}
