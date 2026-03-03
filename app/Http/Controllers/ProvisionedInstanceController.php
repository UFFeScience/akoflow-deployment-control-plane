<?php

namespace App\Http\Controllers;

use App\Exceptions\InstanceNotFoundException;
use App\Http\Resources\InstanceLogResource;
use App\Http\Resources\ProvisionedInstanceResource;
use App\Services\ClusterAuthorizationService;
use App\Services\GetInstanceService;
use App\Services\ListInstanceLogsService;
use App\Services\ListInstancesByClusterService;

class ProvisionedInstanceController extends Controller
{
    public function __construct(
        protected ListInstancesByClusterService $listByClusterService,
        protected GetInstanceService $getInstanceService,
        protected ListInstanceLogsService $logsService,
        protected ClusterAuthorizationService $clusterAuthorizationService,
    ) {}

    public function listByCluster(string $clusterId)
    {
        $this->clusterAuthorizationService->assertUserCanAccessCluster(auth()->user(), $clusterId);

        return ProvisionedInstanceResource::collection($this->listByClusterService->handle($clusterId));
    }

    public function show(string $id)
    {
        $inst = $this->getInstanceService->handle($id);

        if (!$inst) {
            throw new InstanceNotFoundException();
        }

        return new ProvisionedInstanceResource($inst);
    }

    public function logs(string $id)
    {
        $instance = $this->getInstanceService->handle($id);

        if (!$instance) {
            throw new InstanceNotFoundException();
        }

        $logs = $this->logsService->handle($id);

        return InstanceLogResource::collection($logs);
    }
}
