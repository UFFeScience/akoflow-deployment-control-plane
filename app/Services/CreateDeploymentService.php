<?php

namespace App\Services;

use App\Repositories\ClusterRepository;
use App\Repositories\ProvisionedInstanceRepository;
use App\Repositories\InstanceGroupRepository;
use App\Models\Deployment;
use App\Models\ClusterTemplate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CreateClusterService
{
    public function __construct(
        private ClusterRepository $deployments,
        private ProvisionedInstanceRepository $instances,
        private InstanceGroupRepository $instanceGroups,
    )
    {
    }

    public function handle(string $environmentId, array $data): Deployment
    {
        return DB::transaction(function () use ($environmentId, $data) {
            $data['environment_id'] = $environmentId;

            // Default deployment template if not provided — only set if one actually exists
            if (empty($data['cluster_template_id'])) {
                $fallbackTemplateId = ClusterTemplate::value('id');
                if ($fallbackTemplateId) {
                    $data['cluster_template_id'] = $fallbackTemplateId;
                } else {
                    unset($data['cluster_template_id']);
                }
            }

            // Default environment type
            $data['environment_type'] = $data['environment_type'] ?? Deployment::ENVIRONMENT_TYPES[0];

            // Default name
            $data['name'] = $data['name'] ?? 'deployment-' . Str::random(6);

            $instanceGroups = $data['instance_groups'] ?? $data['instances'] ?? [];
            unset($data['instance_groups'], $data['instances']);

            /** @var Deployment $deployment */
            $deployment = $this->deployments->create($data);

            // Create provisioned instances
            foreach ($instanceGroups as $groupDef) {
                $quantity = max(1, (int) ($groupDef['quantity'] ?? 1));

                $group = $this->instanceGroups->create([
                    'cluster_id' => $deployment->id,
                    'instance_type_id' => $groupDef['instance_type_id'],
                    'role' => $groupDef['role'] ?? null,
                    'quantity' => $quantity,
                    'metadata_json' => $groupDef['metadata'] ?? $groupDef['metadata_json'] ?? null,
                ]);

                for ($i = 0; $i < $quantity; $i++) {
                    $this->instances->create([
                        'cluster_id' => $deployment->id,
                        'instance_group_id' => $group->id,
                        'instance_type_id' => $groupDef['instance_type_id'],
                        'role' => $groupDef['role'] ?? null,
                        'status' => 'PROVISIONING',
                    ]);
                }
            }

            return $deployment;
        });
    }
}
