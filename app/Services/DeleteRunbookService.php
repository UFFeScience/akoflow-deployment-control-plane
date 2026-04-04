<?php

namespace App\Services;

use App\Models\EnvironmentTemplateRunbook;

class DeleteRunbookService
{
    public function handle(string $runbookId): void
    {
        EnvironmentTemplateRunbook::findOrFail($runbookId)->delete();
    }
}
