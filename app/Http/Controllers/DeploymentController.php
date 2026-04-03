<?php

namespace App\Http\Controllers;

use App\Exceptions\DeploymentNotFoundException;
use App\Http\Requests\CreateDeploymentRequest;
use App\Http\Resources\DeploymentResource;
use App\Enums\Messages;
use App\Messaging\Contracts\MessageDispatcherInterface;
use App\Services\CreateDeploymentService;
use App\Services\DeploymentWorkflowOrchestratorService;
use App\Services\DestroyDeploymentService;
use App\Services\EnvironmentAuthorizationService;
use App\Services\ListDeploymentsService;

class DeploymentController extends Controller
{
    public function __construct(
        protected ListDeploymentsService                    $listService,
        protected CreateDeploymentService                   $createService,
        protected DestroyDeploymentService                  $destroyService,
        protected EnvironmentAuthorizationService           $environmentAuthorizationService,
        protected DeploymentWorkflowOrchestratorService     $orchestrator,
        protected MessageDispatcherInterface                $dispatcher,
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

        $this->orchestrator->dispatch($deployment);

        return new DeploymentResource($deployment);
    }

    public function destroy(string $id)
    {
        $deployment = $this->destroyService->handle($id);

        return response()->json(['data' => new DeploymentResource($deployment)], 202);
    }
}
