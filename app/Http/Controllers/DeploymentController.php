<?php

namespace App\Http\Controllers;

use App\Exceptions\DeploymentNotFoundException;
use App\Http\Requests\CreateDeploymentRequest;
use App\Http\Resources\DeploymentResource;
use App\Services\CreateDeploymentService;
use App\Services\DeleteDeploymentService;
use App\Services\EnvironmentAuthorizationService;
use App\Services\ListDeploymentsService;

class DeploymentController extends Controller
{
    public function __construct(
        protected ListDeploymentsService          $listService,
        protected CreateDeploymentService         $createService,
        protected DeleteDeploymentService         $deleteService,
        protected EnvironmentAuthorizationService $environmentAuthorizationService,
    ) {}

    public function index(string $environmentId)
    {
        $this->environmentAuthorizationService->assertUserCanAccessEnvironment(auth()->user(), $environmentId);

        return DeploymentResource::collection($this->listService->handle($environmentId));
    }

    public function store(string $environmentId, CreateDeploymentRequest $request)
    {
        $this->environmentAuthorizationService->assertUserCanAccessEnvironment(auth()->user(), $environmentId);

        $deployment = $this->createService->handle($environmentId, $request->validated());

        return new DeploymentResource($deployment);
    }

    public function destroy(string $id)
    {
        $deleted = $this->deleteService->handle($id);

        if (!$deleted) {
            throw new DeploymentNotFoundException();
        }

        return response()->json(null, 204);
    }
}
