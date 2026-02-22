<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateClusterRequest;
use App\Http\Requests\ScaleClusterRequest;
use App\Http\Requests\UpdateClusterNodesRequest;
use App\Http\Resources\ClusterResource;
use App\Services\ListClustersService;
use App\Services\CreateClusterService;
use App\Services\ScaleClusterService;
use App\Services\DeleteClusterService;
use App\Services\UpdateClusterNodesService;

class ClusterController extends Controller
{
    public function __construct(
        protected ListClustersService $listService,
        protected CreateClusterService $createService,
        protected ScaleClusterService $scaleService,
        protected DeleteClusterService $deleteService,
        protected UpdateClusterNodesService $updateNodesService,
    ) {}

    public function index(string $experimentId)
    {
        return ClusterResource::collection($this->listService->handle($experimentId));
    }

    public function store(string $experimentId, CreateClusterRequest $request)
    {
        $cluster = $this->createService->handle($experimentId, $request->validated());
        return new ClusterResource($cluster->load('instanceGroups'));
    }

    public function scale(string $id, ScaleClusterRequest $request)
    {
        $data = $request->validated();
        $cluster = $this->scaleService->handle($id, $data['action'], $data['old_value'], $data['new_value'], $data['triggered_by']);
        if (!$cluster) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(['message' => 'Scale event recorded'], 202);
    }

    public function destroy(string $id)
    {
        $deleted = $this->deleteService->handle($id);
        if (!$deleted) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(null, 204);
    }

    public function updateNodes(string $id, UpdateClusterNodesRequest $request)
    {
        $cluster = $this->updateNodesService->handle($id, $request->validated()['instance_groups']);
        if (!$cluster) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new ClusterResource($cluster->load('instanceGroups'));
    }
}
