<?php

namespace App\Services;

use App\Models\EnvironmentTemplateRunbook;

class UpdateRunbookService
{
    public function handle(string $runbookId, array $data): EnvironmentTemplateRunbook
    {
        $runbook = EnvironmentTemplateRunbook::findOrFail($runbookId);
        $runbook->update($data);

        return $runbook->load('tasks');
    }
}
