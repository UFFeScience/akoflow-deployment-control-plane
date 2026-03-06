<?php

namespace App\Services;

use App\Models\ExperimentTemplate;

class GetExperimentTemplateService
{
    public function handle(string $id): ?ExperimentTemplate
    {
        return ExperimentTemplate::with([
            'versions' => fn($q) => $q->orderBy('created_at', 'desc'),
        ])->find($id);
    }
}
