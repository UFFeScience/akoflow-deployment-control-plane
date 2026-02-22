<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProvisionedInstanceResource;
use App\Http\Resources\InstanceLogResource;
use App\Services\ListInstancesByClusterService;
use App\Services\GetInstanceService;
use App\Services\ListInstanceLogsService;

class ProvisionedInstanceController extends Controller
{
    public function __construct(
        protected ListInstancesByClusterService $listByClusterService,
        protected GetInstanceService $getInstanceService,
        protected ListInstanceLogsService $logsService,
    ) {}

    public function listByCluster(string $clusterId)
    {
        return ProvisionedInstanceResource::collection($this->listByClusterService->handle($clusterId));
    }

    public function show(string $id)
    {
        $inst = $this->getInstanceService->handle($id);
        if (!$inst) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return new ProvisionedInstanceResource($inst);
    }

    public function logs(string $id)
    {
        $instance = $this->getInstanceService->handle($id);
        if (!$instance) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $logs = $this->logsService->handle($id);
        return InstanceLogResource::collection($logs);
    }
}
