<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\ProvisionedInstance;
use App\Repositories\ClusterRepository;
use App\Repositories\InstanceGroupRepository;
use App\Repositories\ProvisionedInstanceRepository;
use Illuminate\Support\Facades\DB;

class UpdateClusterNodesService
{
    public function __construct(
        private ClusterRepository $deployments,
        private InstanceGroupRepository $groups,
        private ProvisionedInstanceRepository $instances,
    ) {
    }

    public function handle(string $clusterId, array $groupsPayload): ?Deployment
    {
        /** @var Deployment|null $deployment */
        $deployment = $this->deployments->find($clusterId);
        if (!$deployment) {
            return null;
        }

        return DB::transaction(function () use ($deployment, $groupsPayload) {
            $deployment->load('instanceGroups');
            $groupMap = $deployment->instanceGroups->keyBy('id');

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
                            'cluster_id' => $deployment->id,
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

            return $deployment->fresh(['instanceGroups']);
        });
    }
}
