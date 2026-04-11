<?php

namespace App\Services;

use App\Models\AnsiblePlaybook;

class GetAnsiblePlaybookService
{
    public function handle(string $playbookId): AnsiblePlaybook
    {
        return AnsiblePlaybook::with(['tasks', 'dependencies'])->findOrFail($playbookId);
    }
}
