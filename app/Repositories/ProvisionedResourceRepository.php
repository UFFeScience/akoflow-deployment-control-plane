<?php

namespace App\Repositories;

use App\Models\ProvisionedResource;

class ProvisionedResourceRepository extends BaseRepository
{
    public function __construct(ProvisionedResource $model)
    {
        parent::__construct($model);
    }

    public function listByDeployment(string $deploymentId)
    {
        return $this->model
            ->with('resourceType.kind')
            ->where('deployment_id', $deploymentId)
            ->get();
    }

    /**
     * Update an existing resource matched by deployment + name, or create it.
     * Used when re-provisioning to avoid duplicate rows.
     */
    public function updateOrCreateByDeploymentAndName(int $deploymentId, ?string $name, array $data): \App\Models\ProvisionedResource
    {
        return $this->model->updateOrCreate(
            ['deployment_id' => $deploymentId, 'name' => $name],
            $data,
        );
    }

    /**
     * Mark every active resource for a deployment as DESTROYED.
     */
    public function markAllDestroyedForDeployment(int $deploymentId): void
    {
        $this->model
            ->where('deployment_id', $deploymentId)
            ->whereNotIn('status', [\App\Models\ProvisionedResource::STATUS_DESTROYED])
            ->update(['status' => \App\Models\ProvisionedResource::STATUS_DESTROYED]);
    }
}
