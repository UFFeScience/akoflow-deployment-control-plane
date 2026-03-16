<?php

namespace App\Services;

use App\Repositories\ExperimentTemplateVersionRepository;
use App\Repositories\ExperimentTemplateTerraformModuleRepository;
use App\Models\ExperimentTemplateVersion;

class AddExperimentTemplateVersionService
{
    public function __construct(
        private ExperimentTemplateVersionRepository      $versions,
        private ExperimentTemplateTerraformModuleRepository $terraformModules,
    ) {}

    public function handle(string $templateId, array $data): ExperimentTemplateVersion
    {
        $previousVersion = $this->versions->getActiveByTemplateId($templateId);

        $data['template_id'] = $templateId;
        $version = $this->versions->create($data);

        if (!array_key_exists('is_active', $data) || $data['is_active']) {
            $this->versions->deactivateOtherVersions($templateId, $version->id);
        }

        if ($previousVersion) {
            $this->cloneTerraformModules($previousVersion->id, $version->id);
        }

        return $version;
    }

    /** Clones all Terraform modules from the previous version so new versions stay provisionable. */
    private function cloneTerraformModules(string $fromVersionId, string $toVersionId): void
    {
        $modules = $this->terraformModules->findAllByVersionId($fromVersionId);

        foreach ($modules as $module) {
            $this->terraformModules->upsertForVersionAndProvider($toVersionId, $module->provider_type, [
                'module_slug'         => $module->module_slug,
                'main_tf'             => $module->main_tf,
                'variables_tf'        => $module->variables_tf,
                'outputs_tf'          => $module->outputs_tf,
                'credential_env_keys' => $module->credential_env_keys,
                'tfvars_mapping_json' => $module->tfvars_mapping_json,
            ]);
        }
    }
}
