<?php

namespace App\Repositories;

use App\Models\TerraformRun;
use Illuminate\Database\Eloquent\Collection;

class TerraformRunRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new TerraformRun());
    }

    public function findByEnvironment(string $environmentId): Collection
    {
        return TerraformRun::where('environment_id', $environmentId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function latestForEnvironment(string $environmentId): ?TerraformRun
    {
        return TerraformRun::where('environment_id', $environmentId)
            ->where('action', TerraformRun::ACTION_APPLY)
            ->latest()
            ->first();
    }

    public function create(array $data): TerraformRun
    {
        return TerraformRun::create($data);
    }

    public function updateStatus(int $id, string $status): void
    {
        TerraformRun::where('id', $id)->update(['status' => $status]);
    }
}
