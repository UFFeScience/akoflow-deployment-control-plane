<?php

namespace App\Services;

use App\Models\Cluster;
use App\Models\Environment;
use Illuminate\Support\Facades\DB;

class CreateEnvironmentWithClusterService
{
    public function __construct(
        private CreateEnvironmentService $createEnvironment,
        private CreateClusterService $createCluster,
    ) {}

    /**
     * Creates an environment and, when cluster data is provided, a cluster —
     * all within a single database transaction so both entities are atomic.
     *
     * @param  string  $projectId
     * @param  array   $data  Validated data from ProvisionEnvironmentRequest
     * @return array{environment: Environment, cluster: Cluster|null}
     */
    public function handle(string $projectId, array $data): array
    {
        return DB::transaction(function () use ($projectId, $data) {
            $clusterData = $data['cluster'] ?? null;
            unset($data['cluster']);

            $environment = $this->createEnvironment->handle($projectId, $data);

            $cluster = null;
            if (!empty($clusterData) && !empty($clusterData['provider_id'])) {
                $cluster = $this->createCluster->handle((string) $environment->id, $clusterData);
            }

            return [
                'environment' => $environment,
                'cluster'     => $cluster,
            ];
        });
    }
}
