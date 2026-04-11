<?php

namespace App\Services;

use App\Models\AnsiblePlaybookRun;

class GetAnsiblePlaybookRunService
{
    public function handle(string $runId): AnsiblePlaybookRun
    {
        return AnsiblePlaybookRun::with('taskHostStatuses')->findOrFail($runId);
    }
}
