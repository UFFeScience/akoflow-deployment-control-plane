<?php

namespace App\Services;

use App\Models\EnvironmentTemplate;

class GetEnvironmentTemplateService
{
    public function handle(string $id): ?EnvironmentTemplate
    {
        return EnvironmentTemplate::with([
            'versions' => fn($q) => $q->orderBy('created_at', 'desc'),
        ])->find($id);
    }
}
