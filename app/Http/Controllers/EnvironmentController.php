<?php

namespace App\Http\Controllers;

use App\Exceptions\EnvironmentNotFoundException;
use App\Http\Requests\CreateEnvironmentRequest;
use App\Http\Resources\EnvironmentResource;
use App\Services\CreateEnvironmentService;
use App\Services\GetEnvironmentService;
use App\Services\ListEnvironmentsService;
use App\Services\ListEnvironmentsByOrganizationService;
use App\Services\OrganizationAuthorizationService;
use App\Services\ProjectAuthorizationService;
use Illuminate\Http\Request;

class EnvironmentController extends Controller
{
    public function __construct(
        protected ListEnvironmentsService $listService,
        protected ListEnvironmentsByOrganizationService $listByOrgService,
        protected CreateEnvironmentService $createService,
        protected GetEnvironmentService $getService,
        protected ProjectAuthorizationService $projectAuthorizationService,
        protected OrganizationAuthorizationService $organizationAuthorizationService,
    ) {}

    public function indexByOrganization(Request $request, int $organizationId)
    {
        $this->organizationAuthorizationService->assertUserBelongsToOrganization($request->user(), $organizationId);

        return EnvironmentResource::collection($this->listByOrgService->handle($organizationId));
    }

    public function index(string $projectId)
    {
        $this->projectAuthorizationService->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        return EnvironmentResource::collection($this->listService->handle($projectId));
    }

    public function store(string $projectId, CreateEnvironmentRequest $request)
    {
        $this->projectAuthorizationService->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $exp = $this->createService->handle($projectId, $request->validated());

        return new EnvironmentResource($exp);
    }

    public function show(string $projectId, string $environmentId)
    {
        $this->projectAuthorizationService->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        $exp = $this->getService->handle($projectId, $environmentId);

        if (!$exp) {
            throw new EnvironmentNotFoundException();
        }

        return new EnvironmentResource($exp);
    }
}
