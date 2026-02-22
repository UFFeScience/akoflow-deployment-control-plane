<?php

namespace App\Services;

use App\Repositories\ClusterRepository;
use App\Repositories\ClusterScalingEventRepository;
use App\Models\Cluster;

class ScaleClusterService
{
    public function __construct(
        private ClusterRepository $clusters,
        private ClusterScalingEventRepository $events
    ) {
    }

    public function handle(string $clusterId, string $action, int $oldValue, int $newValue, string $triggeredBy): ?Cluster
    {
        $cluster = $this->clusters->find($clusterId);
        if (!$cluster) {
            return null;
        }

        $this->events->create([
            'cluster_id' => $clusterId,
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'triggered_by' => $triggeredBy,
        ]);

        return $cluster;
    }
}
