<?php

namespace App\Services;

use App\Models\AnsiblePlaybook;
use App\Models\Deployment;
use App\Models\Environment;
use App\Models\ProvisionedResource;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class AnsibleWorkspaceService
{
    private const BASE_DIR = 'ansible';

    public function workspaceAbsolutePath(Deployment $deployment): string
    {
        return storage_path('app/' . self::BASE_DIR . '/' . $deployment->id);
    }

    /**
     * Build a workspace for a single AnsiblePlaybook execution.
     *
     * @return array{workspace_path: string, provider_type: string, extra_vars: array, inventory_ini: string}
     */
    public function buildForActivity(
        Environment $environment,
        Deployment $deployment,
        string $providerType,
        AnsiblePlaybook $activity,
    ): array {
        $workspacePath = storage_path(
            'app/ansible/' . $deployment->id . '/playbooks/' . $activity->id . '-' . uniqid('', true),
        );

        if (!is_dir($workspacePath)) {
            mkdir($workspacePath, 0755, true);
        }

        if (empty($activity->playbook_yaml)) {
            throw new RuntimeException(
                "AnsiblePlaybook #{$activity->id} '{$activity->name}' has no playbook_yaml."
            );
        }

        file_put_contents($workspacePath . '/playbook.yml', $activity->playbook_yaml);

        $extraVars = ['environment_id' => (string) $environment->id];
        if (!empty($activity->vars_mapping_json)) {
            $extraVars += $this->applyMapping(
                $environment->configuration_json ?? [],
                $activity->vars_mapping_json,
            );
        }

        file_put_contents(
            $workspacePath . '/extra_vars.json',
            json_encode($extraVars, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );

        $sshUser = $extraVars['ansible_user'] ?? null;
        $inventoryIni = $this->buildInventoryFromResources($deployment, $sshUser);
        file_put_contents($workspacePath . '/inventory.ini', $inventoryIni);

        if (!empty($activity->roles_json)) {
            $this->writeRequirements($workspacePath, $activity->roles_json);
        }

        return [
            'workspace_path' => $workspacePath,
            'provider_type'  => $providerType,
            'extra_vars'     => $extraVars,
            'inventory_ini'  => $inventoryIni,
        ];
    }

    private function applyMapping(array $config, array $mapping): array
    {
        $vars = [];

        $expCfg      = $config['environment_configuration'] ?? [];
        $expMappings = $mapping['environment_configuration'] ?? [];

        foreach ($expMappings as $configField => $entry) {
            if (array_key_exists($configField, $expCfg)) {
                [$ansibleVar, $cast] = $this->parseEntry($entry);
                $vars[$ansibleVar]   = $this->castValue($expCfg[$configField], $cast);
            }
        }

        $instCfgs     = $config['instance_configurations'] ?? [];
        $instMappings = $mapping['instance_configurations'] ?? [];

        foreach ($instMappings as $instanceKey => $fieldMappings) {
            $instance = $instCfgs[$instanceKey] ?? [];
            foreach ($fieldMappings as $configField => $entry) {
                if (array_key_exists($configField, $instance)) {
                    [$ansibleVar, $cast] = $this->parseEntry($entry);
                    $vars[$ansibleVar]   = $this->castValue($instance[$configField], $cast);
                }
            }
        }

        return $vars;
    }

    private function parseEntry(mixed $entry): array
    {
        if (is_string($entry)) {
            return [$entry, null];
        }

        return [$entry['ansible_var'] ?? $entry['tf_var'] ?? '', $entry['cast'] ?? null];
    }

    private function castValue(mixed $value, ?string $cast): mixed
    {
        return match ($cast) {
            'int'    => (int) $value,
            'float'  => (float) $value,
            'bool'   => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string) $value,
            'json'   => is_string($value) ? json_decode($value, true) : $value,
            default  => $value,
        };
    }

    private function buildInventoryFromResources(Deployment $deployment, ?string $sshUser = null): string
    {
        $resources = ProvisionedResource::where('deployment_id', $deployment->id)
            ->whereNotNull('public_ip')
            ->where('status', ProvisionedResource::STATUS_RUNNING)
            ->get();

        if ($resources->isEmpty()) {
            return "[all]\n# No provisioned resources found for deployment {$deployment->id}\n";
        }

        $lines = ['[provisioned]'];
        foreach ($resources as $resource) {
            $user = $sshUser ?? $resource->metadata_json['ssh_user'] ?? 'ubuntu';
            $hostName = $resource->name ?: $resource->public_ip;
            $lines[] = "{$hostName} ansible_host={$resource->public_ip} ansible_user={$user}";
        }

        return implode("\n", $lines) . "\n";
    }

    private function writeRequirements(string $workspacePath, array $roles): void
    {
        $requirements = [];

        foreach ($roles as $role) {
            if (is_string($role) && $role !== '') {
                $requirements[] = ['name' => $role];
            } elseif (is_array($role) && !empty($role['name'])) {
                $requirements[] = $role;
            }
        }

        if (!empty($requirements)) {
            file_put_contents($workspacePath . '/requirements.yml', $this->formatRequirementsYaml($requirements));
        }
    }

    private function formatRequirementsYaml(array $requirements): string
    {
        $lines = [];

        foreach ($requirements as $requirement) {
            $lines[] = '- name: ' . ($requirement['name'] ?? '');

            foreach ($requirement as $key => $value) {
                if ($key === 'name') {
                    continue;
                }

                if (is_array($value)) {
                    $lines[] = '  ' . $key . ':';
                    foreach ($value as $nestedKey => $nestedValue) {
                        $lines[] = '    ' . $nestedKey . ': ' . $this->stringifyYamlValue($nestedValue);
                    }
                    continue;
                }

                $lines[] = '  ' . $key . ': ' . $this->stringifyYamlValue($value);
            }
        }

        return implode("\n", $lines) . "\n";
    }

    private function stringifyYamlValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        $stringValue = (string) $value;

        if ($stringValue === '' || preg_match('/[:#\n\r\t\-]|^\s|\s$/', $stringValue)) {
            return '"' . str_replace('"', '\\"', $stringValue) . '"';
        }

        return $stringValue;
    }
}

