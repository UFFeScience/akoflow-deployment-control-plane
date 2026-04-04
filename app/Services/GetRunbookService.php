<?php

namespace App\Services;

use App\Models\EnvironmentTemplateRunbook;

class GetRunbookService
{
    public function handle(string $runbookId): EnvironmentTemplateRunbook
    {
        return EnvironmentTemplateRunbook::with('tasks')->findOrFail($runbookId);
    }
}
