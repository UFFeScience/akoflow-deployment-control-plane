<?php

namespace App\Http\Controllers;

use Exception;
use App\Exceptions\ProjectNotFoundException;
use App\Http\Requests\CreateProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Repositories\ProjectRepository;
use App\Services\CreateProjectService;
use App\Services\DeleteProjectService;
use App\Services\GetProjectByIdService;
use App\Services\ListProjectsByOrganizationService;
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
        private ProjectRepository $projectRepository,
    ) {}

    public function create(CreateProjectRequest $request, int $organizationId): JsonResponse
    {
        try {
            $project = $this->createProjectService->execute(
                $organizationId,
                $request->validated()
            );

            return response()->json([
                'message' => 'Project created successfully',
                'data' => new ProjectResource($project),
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function listByOrganization(Request $request, int $organizationId): JsonResponse
    {
        try {
            $projects = $this->listProjectsByOrganizationService->execute($organizationId);

            return response()->json([
                'message' => 'Projects retrieved successfully',
                'data' => ProjectResource::collection($projects),
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getById(Request $request, int $organizationId, int $projectId): JsonResponse
    {
        try {
            $project = $this->getProjectByIdService->execute($projectId);

            if ($project->organization_id !== $organizationId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return response()->json([
                'message' => 'Project retrieved successfully',
                'data' => new ProjectResource($project),
            ]);
        } catch (ProjectNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function update(UpdateProjectRequest $request, int $organizationId, int $projectId): JsonResponse
    {
        try {
            $project = $this->projectRepository->findById($projectId);

            if (!$project) {
                throw new ProjectNotFoundException();
            }

            if ($project->organization_id !== $organizationId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $updated = $this->updateProjectService->execute($project, $request->validated());

            return response()->json([
                'message' => 'Project updated successfully',
                'data' => new ProjectResource($updated),
            ]);
        } catch (ProjectNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function delete(Request $request, int $organizationId, int $projectId): JsonResponse
    {
        try {
            $project = $this->projectRepository->findById($projectId);

            if (!$project) {
                throw new ProjectNotFoundException();
            }

            if ($project->organization_id !== $organizationId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $this->deleteProjectService->execute($project);

            return response()->json([
                'message' => 'Project deleted successfully',
            ]);
        } catch (ProjectNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
