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

    public function findByExperiment(string $experimentId): Collection
    {
        return TerraformRun::where('experiment_id', $experimentId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function latestForExperiment(string $experimentId): ?TerraformRun
    {
        return TerraformRun::where('experiment_id', $experimentId)
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
