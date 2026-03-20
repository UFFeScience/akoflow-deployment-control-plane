<?php

namespace App\Services;

use App\Repositories\EnvironmentRepository;
use App\Repositories\EnvironmentTemplateVersionRepository;
use App\Models\Environment;

class CreateEnvironmentService
{
    public function __construct(
        private EnvironmentRepository $environments,
        private EnvironmentTemplateVersionRepository $templateVersions,
    ) {
    }

    public function handle(string $projectId, array $data): Environment
    {
        $data['project_id'] = $projectId;

        // Accept frontend-sent template version ID (snake_case from API payload)
        // The field can arrive as 'environment_template_version_id' (already correct) or
        // as 'template_version_id' (legacy alias from the old wizard)
        if (!isset($data['environment_template_version_id']) && isset($data['template_version_id'])) {
            $data['environment_template_version_id'] = $data['template_version_id'];
        }
        unset($data['template_version_id']);

        // Build a complete configuration_json by merging the template version's
        // default field values with the user-provided partial configuration.
        // This ensures the stored configuration always contains the full object.
        if (!empty($data['environment_template_version_id'])) {
            $templateVersion = $this->templateVersions->find((string) $data['environment_template_version_id']);

            if ($templateVersion && !empty($templateVersion->definition_json)) {
                $definition  = $templateVersion->definition_json;
                $userConfig  = $data['configuration_json'] ?? [];
                $data['configuration_json'] = $this->buildCompleteConfiguration($definition, $userConfig);
            }
        }

        $environment = $this->environments->create($data);

        return $environment;
    }

    /**
     * Merges user-supplied values on top of the defaults extracted from the
     * template's definition_json so the stored configuration_json is always
     * a complete snapshot of every known field.
     */
    private function buildCompleteConfiguration(array $definition, array $userConfig): array
    {
        $config = [];

        // 1. environment_configuration – flatten all section defaults, then overlay user values
        if (!empty($definition['environment_configuration']['sections'])) {
            $defaults = $this->extractDefaultsFromSections($definition['environment_configuration']['sections']);
            $userExpConfig = $userConfig['environment_configuration'] ?? [];
            $config['environment_configuration'] = array_merge($defaults, $userExpConfig);
        } elseif (!empty($userConfig['environment_configuration'])) {
            $config['environment_configuration'] = $userConfig['environment_configuration'];
        }

        // 2. instance_configurations – same process per instance key
        if (!empty($definition['instance_configurations'])) {
            $config['instance_configurations'] = [];
            foreach ($definition['instance_configurations'] as $key => $instanceDef) {
                $defaults = $this->extractDefaultsFromSections($instanceDef['sections'] ?? []);
                $userInstanceConfig = $userConfig['instance_configurations'][$key] ?? [];
                $config['instance_configurations'][$key] = array_merge($defaults, $userInstanceConfig);
            }
        } elseif (!empty($userConfig['instance_configurations'])) {
            $config['instance_configurations'] = $userConfig['instance_configurations'];
        }

        // 3. lifecycle_hooks – pass through as-is
        if (!empty($userConfig['lifecycle_hooks'])) {
            $config['lifecycle_hooks'] = $userConfig['lifecycle_hooks'];
        }

        // 4. Any extra top-level keys the user sent that are not handled above
        foreach ($userConfig as $key => $value) {
            if (!array_key_exists($key, $config)) {
                $config[$key] = $value;
            }
        }

        return $config;
    }

    /**
     * Collects { field_name => default_value } from an array of section
     * definitions, skipping fields without a declared default.
     *
     * @param  array<int, array{fields?: array<int, array{name: string, default?: mixed}>}> $sections
     * @return array<string, mixed>
     */
    private function extractDefaultsFromSections(array $sections): array
    {
        $defaults = [];
        foreach ($sections as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                if (isset($field['name']) && array_key_exists('default', $field)) {
                    $defaults[$field['name']] = $field['default'];
                }
            }
        }
        return $defaults;
    }
}
