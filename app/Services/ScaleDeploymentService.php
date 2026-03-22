<?php

namespace App\Services;

use App\Repositories\ClusterRepository;
use App\Repositories\ClusterScalingEventRepository;
use App\Models\Deployment;

class ScaleClusterService
{
    public function __construct(
        private ClusterRepository $deployments,
        private ClusterScalingEventRepository $events
    ) {
    }

    public function handle(string $clusterId, string $action, int $oldValue, int $newValue, string $triggeredBy): ?Deployment
    {
        $deployment = $this->deployments->find($clusterId);
        if (!$deployment) {
            return null;
        }

        $this->events->create([
            'cluster_id' => $clusterId,
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'triggered_by' => $triggeredBy,
        ]);

        return $deployment;
    }
}
