<?php

namespace App\Http\Controllers;

use App\Exceptions\InstanceNotFoundException;
use App\Http\Resources\ProvisionedResourceResource;
use App\Http\Resources\RunLogResource;
use App\Services\DeploymentAuthorizationService;
use App\Services\GetResourceService;
use App\Services\ListRunLogsService;
use App\Services\ListResourcesByDeploymentService;
use Illuminate\Http\Request;

class ProvisionedResourceController extends Controller
{
    public function __construct(
        protected ListResourcesByDeploymentService $listByDeploymentService,
        protected GetResourceService               $getResourceService,
        protected ListRunLogsService               $logsService,
        protected DeploymentAuthorizationService   $deploymentAuthorizationService,
    ) {}

    public function listByDeployment(string $deploymentId)
    {
        $this->deploymentAuthorizationService->assertUserCanAccessDeployment(auth()->user(), $deploymentId);

        return ProvisionedResourceResource::collection($this->listByDeploymentService->handle($deploymentId));
    }

    public function show(string $id)
    {
        $resource = $this->getResourceService->handle($id);

        if (!$resource) {
            throw new InstanceNotFoundException();
        }

        return new ProvisionedResourceResource($resource);
    }

    public function logs(string $id, Request $request)
    {
        $resource = $this->getResourceService->handle($id);

        if (!$resource) {
            throw new InstanceNotFoundException();
        }

        $afterId = $request->integer('after_id', 0) ?: null;

        return RunLogResource::collection(
            $this->logsService->handleByResource($id, $afterId)
        );
    }
}
