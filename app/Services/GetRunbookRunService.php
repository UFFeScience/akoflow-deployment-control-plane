<?php

namespace App\Services;

use App\Models\RunbookRun;

class GetRunbookRunService
{
    public function handle(string $runId): RunbookRun
    {
        return RunbookRun::with('taskRuns')->findOrFail($runId);
    }
}
