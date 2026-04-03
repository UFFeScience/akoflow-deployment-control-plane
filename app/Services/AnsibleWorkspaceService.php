<?php

namespace App\Services;

use App\Models\AnsibleRun;
use App\Models\Deployment;
use App\Models\Environment;
use App\Models\EnvironmentTemplateAnsiblePlaybook;
use App\Models\EnvironmentTemplateProviderConfiguration;
use App\Models\ProvisionedResource;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

/**
 * Builds the Ansible workspace directory for a given Deployment.
 *
 * Directory layout (storage/app/ansible/{deployment_id}/):
 *   playbook.yml       — from EnvironmentTemplateAnsiblePlaybook.playbook_yaml
 *   inventory.ini      — generated from inventory_template + config OR from ProvisionedResources
 *   requirements.yml   — from roles_json (when present)
 *   extra_vars.json    — produced by applying vars_mapping_json to environment.configuration_json
 *
 * ── vars_mapping_json format ──────────────────────────────────────────────────
 * Same semantics as TerraformWorkspaceService::tfvars_mapping_json.
 * Each leaf may be:
 *   a) a plain string  → Ansible variable name, value passed as-is
 *   b) an object       → { "ansible_var": "var_name", "cast": "int|float|bool|string|json" }
 *
 * ── inventory_template placeholders ──────────────────────────────────────────
 * Simple {{ key }} substitution against environment_configuration values.
 * When inventory_template is null in a Cloud environment, the inventory is
 * auto-generated from the ProvisionedResource records (IPs from Terraform).
 *
 * Credentials are NOT stored here — injected as process env vars by
 * AnsibleProcessRunnerService.
 */
class AnsibleWorkspaceService
{
    private const BASE_DIR = 'ansible';

    public function workspaceAbsolutePath(Deployment $deployment): string
    {
        return storage_path('app/' . self::BASE_DIR . '/' . $deployment->id);
    }

    /**
     * Build the workspace directory and write all Ansible files from DB.
     *
     * @return array{workspace_path: string, provider_type: string, extra_vars: array, inventory_ini: string}
     * @throws RuntimeException when no playbook is registered for the template version.
     */
    public function build(Environment $environment, Deployment $deployment, string $providerType): array
    {
        $playbook = $this->loadPlaybook($environment, $providerType);

        $workspacePath = $this->workspaceAbsolutePath($deployment);
        if (!is_dir($workspacePath)) {
            mkdir($workspacePath, 0755, true);
        }

        // 1. Write playbook.yml
        file_put_contents($workspacePath . '/playbook.yml', $playbook->playbook_yaml);

        // 2. Build and write extra_vars.json
        $extraVars = $this->buildExtraVars($environment, $playbook);
        file_put_contents(
            $workspacePath . '/extra_vars.json',
            json_encode($extraVars, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );

        // 3. Build and write inventory.ini
        $inventoryIni = $this->buildInventory($environment, $deployment, $playbook, $extraVars);
        file_put_contents($workspacePath . '/inventory.ini', $inventoryIni);

        // 4. Write requirements.yml if roles are defined
        if (!empty($playbook->roles_json)) {
            $this->writeRequirements($workspacePath, $playbook->roles_json);
        }

        return [
            'workspace_path' => $workspacePath,
            'provider_type'  => $providerType,
            'extra_vars'     => $extraVars,
            'inventory_ini'  => $inventoryIni,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Playbook loading
    // ─────────────────────────────────────────────────────────────────────────

    private function loadPlaybook(Environment $environment, string $providerType): EnvironmentTemplateAnsiblePlaybook
    {
        if (empty($environment->environment_template_version_id)) {
            throw new RuntimeException(
                "Environment {$environment->id} has no template version assigned.",
            );
        }

        $version = $environment->templateVersion()
            ->with('providerConfigurations.ansiblePlaybook')
            ->first();

        $playbook = $this->resolvePlaybookFromConfigs(
            $version?->providerConfigurations ?? collect(),
            $providerType,
        );

        if (!$playbook) {
            throw new RuntimeException(
                "Template version #{$environment->environment_template_version_id} " .
                "has no AnsiblePlaybook configured for provider '{$providerType}'.",
            );
        }

        if (empty($playbook->playbook_yaml)) {
            throw new RuntimeException(
                "AnsiblePlaybook #{$playbook->id} has no playbook_yaml content.",
            );
        }

        return $playbook;
    }

    private function resolvePlaybookFromConfigs(Collection $configs, string $providerType): ?EnvironmentTemplateAnsiblePlaybook
    {
        $upper  = strtoupper($providerType);
        $config = $configs->first(function (EnvironmentTemplateProviderConfiguration $c) use ($upper) {
            return in_array($upper, array_map('strtoupper', $c->applies_to_providers ?? []), true);
        });
        if ($config?->ansiblePlaybook) {
            return $config->ansiblePlaybook;
        }

        $default = $configs->first(fn(EnvironmentTemplateProviderConfiguration $c) => empty($c->applies_to_providers));
        return $default?->ansiblePlaybook;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // extra_vars building — driven by vars_mapping_json
    // ─────────────────────────────────────────────────────────────────────────

    private function buildExtraVars(Environment $environment, EnvironmentTemplateAnsiblePlaybook $playbook): array
    {
        $base    = ['environment_id' => (string) $environment->id];
        $mapping = $playbook->vars_mapping_json ?? [];

        if (empty($mapping)) {
            return $base;
        }

        return $base + $this->applyMapping($environment->configuration_json ?? [], $mapping);
    }

    private function applyMapping(array $config, array $mapping): array
    {
        $vars = [];

        // ── environment_configuration fields ─────────────────────────────
        $expCfg      = $config['environment_configuration'] ?? [];
        $expMappings = $mapping['environment_configuration'] ?? [];

        foreach ($expMappings as $configField => $entry) {
            if (array_key_exists($configField, $expCfg)) {
                [$ansibleVar, $cast] = $this->parseEntry($entry);
                $vars[$ansibleVar]   = $this->castValue($expCfg[$configField], $cast);
            }
        }

        // ── instance_configurations fields ───────────────────────────────
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

    // ─────────────────────────────────────────────────────────────────────────
    // Inventory building — two modes
    // ─────────────────────────────────────────────────────────────────────────

    private function buildInventory(
        Environment $environment,
        Deployment $deployment,
        EnvironmentTemplateAnsiblePlaybook $playbook,
        array $extraVars = [],
    ): string {
        // Mode 1: use template from DB (HPC/ON_PREM — machines pre-exist)
        if (!empty($playbook->inventory_template)) {
            return $this->renderInventoryTemplate(
                $playbook->inventory_template,
                $environment->configuration_json['environment_configuration'] ?? [],
            );
        }

        // Mode 2: auto-generate from ProvisionedResource rows (post-Terraform Cloud)
        // ansible_user can be overridden by the extra_vars resolved from environment config
        $sshUser = $extraVars['ansible_user'] ?? null;
        return $this->generateInventoryFromResources($deployment, $sshUser);
    }

    /**
     * Simple {{ key }} substitution against a flat values array.
     */
    private function renderInventoryTemplate(string $template, array $values): string
    {
        foreach ($values as $key => $value) {
            $template = str_replace('{{ ' . $key . ' }}', (string) $value, $template);
            $template = str_replace('{{' . $key . '}}', (string) $value, $template);
        }
        return $template;
    }

    private function generateInventoryFromResources(Deployment $deployment, ?string $sshUser = null): string
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
            // Priority: caller-supplied > resource metadata > fallback 'ubuntu'
            $user   = $sshUser ?? $resource->metadata_json['ssh_user'] ?? 'ubuntu';
            $lines[] = "{$resource->public_ip} ansible_user={$user}";
        }

        return implode("\n", $lines) . "\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // requirements.yml
    // ─────────────────────────────────────────────────────────────────────────

    private function writeRequirements(string $workspacePath, array $roles): void
    {
        $lines = ['roles:'];
        foreach ($roles as $role) {
            if (is_string($role)) {
                $lines[] = "  - name: {$role}";
            } else {
                $line = "  - name: {$role['name']}";
                if (!empty($role['version'])) {
                    $line .= "\n    version: {$role['version']}";
                }
                $lines[] = $line;
            }
        }

        file_put_contents($workspacePath . '/requirements.yml', implode("\n", $lines) . "\n");
    }
}
