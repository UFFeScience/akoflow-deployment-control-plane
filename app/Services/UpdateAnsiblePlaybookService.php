<?php

namespace App\Services;

use App\Models\AnsiblePlaybook;

class UpdateAnsiblePlaybookService
{
    public function handle(string $playbookId, array $data): AnsiblePlaybook
    {
        $activity = AnsiblePlaybook::findOrFail($playbookId);
        $activity->update($data);

        return $activity->load('tasks');
    }
}
