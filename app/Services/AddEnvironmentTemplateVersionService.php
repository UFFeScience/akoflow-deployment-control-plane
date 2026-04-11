<?php

namespace App\Services;

use App\Models\AnsiblePlaybook;
use App\Models\EnvironmentTemplateVersion;
use App\Repositories\EnvironmentTemplateVersionRepository;
use App\Repositories\EnvironmentTemplateProviderConfigurationRepository;
use App\Repositories\EnvironmentTemplateTerraformModuleRepository;

class AddEnvironmentTemplateVersionService
{
    public function __construct(
        private EnvironmentTemplateVersionRepository               $versions,
        private EnvironmentTemplateProviderConfigurationRepository $configRepository,
        private EnvironmentTemplateTerraformModuleRepository       $terraformRepository,
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

            foreach ($config->playbooks as $activity) {
                AnsiblePlaybook::create([
                    'provider_configuration_id' => $newConfig->id,
                    'name'                      => $activity->name,
                    'description'               => $activity->description,
                    'trigger'                   => $activity->trigger,
                    'playbook_slug'             => $activity->playbook_slug,
                    'playbook_yaml'             => $activity->playbook_yaml,
                    'inventory_template'        => $activity->inventory_template,
                    'vars_mapping_json'         => $activity->vars_mapping_json,
                    'outputs_mapping_json'      => $activity->outputs_mapping_json,
                    'credential_env_keys'       => $activity->credential_env_keys,
                    'roles_json'                => $activity->roles_json,
                    'position'                  => $activity->position,
                    'enabled'                   => $activity->enabled,
                ]);
            }
        }
    }
}

