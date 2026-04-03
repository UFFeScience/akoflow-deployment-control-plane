<?php

namespace App\Services;

use App\Models\EnvironmentTemplate;
use Illuminate\Support\Collection;

class ListEnvironmentTemplateVersionsService
{
    public function handle(string $templateId): ?Collection
    {
        $template = EnvironmentTemplate::find($templateId);
        if (!$template) {
            return null;
        }

        return $template->versions()
            ->with(['providerConfigurations.terraformModule', 'providerConfigurations.ansiblePlaybook'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
