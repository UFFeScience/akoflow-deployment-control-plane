<?php

namespace App\Services;

use App\Models\ExperimentTemplate;
use Illuminate\Support\Collection;

class ListExperimentTemplateVersionsService
{
    public function handle(string $templateId): ?Collection
    {
        $template = ExperimentTemplate::find($templateId);
        if (!$template) {
            return null;
        }

        return $template->versions()
            ->with('terraformModule')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
