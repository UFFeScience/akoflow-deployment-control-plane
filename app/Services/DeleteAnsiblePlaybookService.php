<?php

namespace App\Services;

use App\Models\AnsiblePlaybook;

class DeleteAnsiblePlaybookService
{
    public function handle(string $playbookId): void
    {
        AnsiblePlaybook::findOrFail($playbookId)->delete();
    }
}
