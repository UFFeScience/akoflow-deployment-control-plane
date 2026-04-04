<?php

namespace App\Repositories;

use App\Models\RunbookRun;
use Illuminate\Database\Eloquent\Collection;

class RunbookRunRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new RunbookRun());
    }

    public function findByDeployment(string $deploymentId): Collection
    {
        return RunbookRun::where('deployment_id', $deploymentId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $data): RunbookRun
    {
        return RunbookRun::create($data);
    }
}
