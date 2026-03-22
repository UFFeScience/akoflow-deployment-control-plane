<?php

namespace App\Http\Controllers;

use App\Exceptions\EnvironmentNotFoundException;
use App\Http\Requests\CreateEnvironmentRequest;
use App\Http\Requests\ProvisionEnvironmentRequest;
use App\Http\Resources\ClusterResource;
use App\Http\Resources\EnvironmentResource;
use App\Services\CreateEnvironmentService;
use App\Services\CreateEnvironmentWithClusterService;
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
        protected CreateEnvironmentWithClusterService $provisionService,
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

    public function provision(string $projectId, ProvisionEnvironmentRequest $request)
    {
        $this->projectAuthorizationService->assertUserCanAccessProjectById(auth()->user(), (int) $projectId);

        ['environment' => $environment, 'deployment' => $deployment] =
            $this->provisionService->handle($projectId, $request->validated());

        $data = (new EnvironmentResource($environment))->toArray($request);

        if ($deployment) {
            $data['deployment'] = (new ClusterResource($deployment->load('instanceGroups')))->toArray($request);
        }

        return response()->json(['data' => $data], 201);
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
