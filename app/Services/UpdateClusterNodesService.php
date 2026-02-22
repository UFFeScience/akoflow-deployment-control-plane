<?php

namespace App\Services;

use App\Models\Cluster;
use App\Models\ProvisionedInstance;
use App\Repositories\ClusterRepository;
use App\Repositories\InstanceGroupRepository;
use App\Repositories\ProvisionedInstanceRepository;
use Illuminate\Support\Facades\DB;

class UpdateClusterNodesService
{
    public function __construct(
        private ClusterRepository $clusters,
        private InstanceGroupRepository $groups,
        private ProvisionedInstanceRepository $instances,
    ) {
    }

    public function handle(string $clusterId, array $groupsPayload): ?Cluster
    {
        /** @var Cluster|null $cluster */
        $cluster = $this->clusters->find($clusterId);
        if (!$cluster) {
            return null;
        }

        return DB::transaction(function () use ($cluster, $groupsPayload) {
            $cluster->load('instanceGroups');
            $groupMap = $cluster->instanceGroups->keyBy('id');

            foreach ($groupsPayload as $groupData) {
                $group = $groupMap->get($groupData['id']);
                if (!$group) {
                    continue;
                }

                $target = (int) $groupData['quantity'];
                $current = (int) $group->quantity;
                if ($target === $current) {
                    continue;
                }

                if ($target > $current) {
                    $delta = $target - $current;
                    for ($i = 0; $i < $delta; $i++) {
                        $this->instances->create([
                            'cluster_id' => $cluster->id,
                            'instance_group_id' => $group->id,
                            'instance_type_id' => $group->instance_type_id,
                            'role' => $group->role,
                            'status' => ProvisionedInstance::STATUSES_PROVISIONING[0],
                        ]);
                    }
                } else {
                    $delta = $current - $target;
                    $toMark = $this->instances->listByGroup($group->id)
                        ->whereNotIn('status', [ProvisionedInstance::STATUS_TERMINATED, ProvisionedInstance::STATUS_REMOVING])
                        ->take($delta)
                        ->get();

                    foreach ($toMark as $instance) {
                        $instance->status = ProvisionedInstance::STATUS_REMOVING;
                        $instance->save();
                    }
                }

                $group->quantity = $target;
                $group->save();
            }

            return $cluster->fresh(['instanceGroups']);
        });
    }
}
