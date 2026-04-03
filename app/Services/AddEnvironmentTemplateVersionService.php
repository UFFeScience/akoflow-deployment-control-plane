<?php

namespace App\Services;

use App\Repositories\EnvironmentTemplateVersionRepository;
use App\Repositories\EnvironmentTemplateProviderConfigurationRepository;
use App\Repositories\EnvironmentTemplateTerraformModuleRepository;
use App\Repositories\EnvironmentTemplateAnsiblePlaybookRepository;
use App\Models\EnvironmentTemplateVersion;

class AddEnvironmentTemplateVersionService
{
    public function __construct(
        private EnvironmentTemplateVersionRepository               $versions,
        private EnvironmentTemplateProviderConfigurationRepository $configRepository,
        private EnvironmentTemplateTerraformModuleRepository       $terraformRepository,
        private EnvironmentTemplateAnsiblePlaybookRepository       $ansibleRepository,
    ) {}

    public function handle(string $templateId, array $data): EnvironmentTemplateVersion
    {
        $previousVersion = $this->versions->getActiveByTemplateId($templateId);

        $data['template_id'] = $templateId;
        $version = $this->versions->create($data);

        if (!array_key_exists('is_active', $data) || $data['is_active']) {
            $this->versions->deactivateOtherVersions($templateId, $version->id);
        }

        if ($previousVersion) {
            $this->cloneProviderConfigurations($previousVersion->id, $version->id);
        }

        return $version;
    }

    private function cloneProviderConfigurations(string $fromVersionId, string $toVersionId): void
    {
        $configs = $this->configRepository->findAllByVersionId($fromVersionId);

        foreach ($configs as $config) {
            $newConfig = $this->configRepository->createForVersion($toVersionId, [
                'name'                 => $config->name,
                'applies_to_providers' => $config->applies_to_providers ?? [],
            ]);

            if ($config->terraformModule) {
                $tf = $config->terraformModule;
                $this->terraformRepository->upsertForConfiguration((string) $newConfig->id, [
                    'module_slug'         => $tf->module_slug,
                    'main_tf'             => $tf->main_tf,
                    'variables_tf'        => $tf->variables_tf,
                    'outputs_tf'          => $tf->outputs_tf,
                    'credential_env_keys' => $tf->credential_env_keys,
                    'tfvars_mapping_json' => $tf->tfvars_mapping_json,
                    'outputs_mapping_json'=> $tf->outputs_mapping_json,
                ]);
            }

            if ($config->ansiblePlaybook) {
                $ans = $config->ansiblePlaybook;
                $this->ansibleRepository->upsertForConfiguration((string) $newConfig->id, [
                    'playbook_slug'       => $ans->playbook_slug,
                    'playbook_yaml'       => $ans->playbook_yaml,
                    'inventory_template'  => $ans->inventory_template,
                    'credential_env_keys' => $ans->credential_env_keys,
                    'vars_mapping_json'   => $ans->vars_mapping_json,
                    'outputs_mapping_json'=> $ans->outputs_mapping_json,
                    'roles_json'          => $ans->roles_json,
                ]);
            }
        }
    }
}

