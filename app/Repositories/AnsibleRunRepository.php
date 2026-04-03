<?php

namespace App\Repositories;

use App\Models\AnsibleRun;
use Illuminate\Database\Eloquent\Collection;

class AnsibleRunRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new AnsibleRun());
    }

    public function findByDeployment(string $deploymentId): Collection
    {
        return AnsibleRun::where('deployment_id', $deploymentId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function latestForDeployment(string $deploymentId, string $action = AnsibleRun::ACTION_CONFIGURE): ?AnsibleRun
    {
        return AnsibleRun::where('deployment_id', $deploymentId)
            ->where('action', $action)
            ->latest()
            ->first();
    }

    public function create(array $data): AnsibleRun
    {
        return AnsibleRun::create($data);
    }

    public function updateStatus(int $id, string $status): void
    {
        AnsibleRun::where('id', $id)->update(['status' => $status]);
    }
}
