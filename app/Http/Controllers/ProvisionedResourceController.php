<?php

namespace App\Http\Controllers;

use App\Exceptions\InstanceNotFoundException;
use App\Http\Resources\ProvisionedResourceResource;
use App\Http\Resources\ResourceLogResource;
use App\Services\DeploymentAuthorizationService;
use App\Services\GetResourceService;
use App\Services\ListResourceLogsService;
use App\Services\ListResourcesByDeploymentService;

class ProvisionedResourceController extends Controller
{
    public function __construct(
        protected ListResourcesByDeploymentService $listByDeploymentService,
        protected GetResourceService               $getResourceService,
        protected ListResourceLogsService          $logsService,
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

    public function logs(string $id)
    {
        $resource = $this->getResourceService->handle($id);

        if (!$resource) {
            throw new InstanceNotFoundException();
        }

        return ResourceLogResource::collection($this->logsService->handle($id));
    }
}
