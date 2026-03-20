<?php

namespace App\Services;

use App\Models\Environment;
use App\Models\EnvironmentTemplateTerraformModule;
use RuntimeException;

/**
 * Generic Terraform workspace builder.
 *
 * All HCL content, variable mappings, type casts, and provider configuration
 * come exclusively from the EnvironmentTemplateTerraformModule record stored in
 * the database. No template- or provider-specific logic lives here.
 *
 * ── tfvars_mapping_json format ────────────────────────────────────────────────
 *
 * Each leaf entry can be:
 *   a) a plain string  → Terraform variable name, value passed as-is
 *   b) an object       → { "tf_var": "var_name", "cast": "int|float|bool|string|json" }
 *
 * Example:
 * {
 *   "environment_configuration": {
 *     "nvflare_version": "nvflare_version",
 *     "fl_rounds":       { "tf_var": "fl_rounds", "cast": "int" },
 *     "enable_tls":      { "tf_var": "enable_tls", "cast": "bool" }
 *   },
 *   "instance_configurations": {
 *     "nvflare-server": {
 *       "instance_type":  "server_instance_type",
 *       "disk_size_gb":   { "tf_var": "server_disk_size_gb", "cast": "int" }
 *     }
 *   }
 * }
 *
 * ── Directory layout (storage/app/terraform/{environment_id}/) ─────────────────
 *   main.tf                – from DB main_tf
 *   variables.tf           – from DB variables_tf (optional)
 *   outputs.tf             – from DB outputs_tf (optional)
 *   terraform.tfvars.json  – produced by applying tfvars_mapping_json
 *
 * Provider credentials are NOT stored here — they are injected directly into
 * the Terraform process environment by TerraformProcessRunnerService.
 */
class TerraformWorkspaceService
{
    private const BASE_DIR = 'terraform';

    public function workspaceRelativePath(Environment $environment): string
    {
        return self::BASE_DIR . '/' . $environment->id;
    }

    public function workspaceAbsolutePath(Environment $environment): string
    {
        return storage_path('app/' . $this->workspaceRelativePath($environment));
    }

    /**
     * Build the workspace directory and write all Terraform files from DB.
     *
     * @param  string|null  $providerType  Target cloud provider. When null the first
     *                                     module found for the version is used.
     * @throws \RuntimeException when no TerraformModule is registered for the template version.
     * @return array{workspace_path: string, provider_type: string, tfvars: array}
     */
    public function build(Environment $environment, ?string $providerType = null): array
    {
        $tfModule = $this->loadModule($environment, $providerType);

        $workspacePath = $this->workspaceAbsolutePath($environment);
        if (!is_dir($workspacePath)) {
            mkdir($workspacePath, 0755, true);
        }

        // 1. Write HCL files from DB
        $this->writeHclFiles($tfModule, $workspacePath);

        // 2. Build tfvars by applying DB mapping to environment's configuration_json
        $tfvars = $this->buildTfvars($environment, $tfModule);

        // 3. Write tfvars.json
        file_put_contents(
            $workspacePath . '/terraform.tfvars.json',
            json_encode($tfvars, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );

        return [
            'workspace_path' => $workspacePath,
            'provider_type'  => $tfModule->provider_type ?? 'unknown',
            'tfvars'         => $tfvars,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Module loading
    // ─────────────────────────────────────────────────────────────────────────

    private function loadModule(Environment $environment, ?string $providerType = null): EnvironmentTemplateTerraformModule
    {
        if (empty($environment->environment_template_version_id)) {
            throw new RuntimeException(
                "Environment {$environment->id} has no template version assigned. A TerraformModule cannot be resolved without a template version.",
            );
        }

        $version = $environment->templateVersion()->with('terraformModules')->first();

        if ($providerType) {
            $module = $version?->terraformModules->firstWhere('provider_type', $providerType);
        } else {
            $module = $version?->terraformModules->first();
        }

        if (!$module) {
            $hint = $providerType ? "provider '{$providerType}'" : 'any provider';
            throw new RuntimeException(
                "Template version #{$environment->environment_template_version_id} " .
                "has no TerraformModule configured for {$hint}. " .
                'Register one via PUT /environment-templates/{id}/versions/{versionId}/terraform-modules/{providerType}.',
            );
        }

        if (empty($module->main_tf)) {
            throw new RuntimeException(
                "TerraformModule #{$module->id} has no main_tf content. " .
                'Set the main_tf field with valid HCL before provisioning.',
            );
        }

        return $module;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HCL file writing
    // ─────────────────────────────────────────────────────────────────────────

    private function writeHclFiles(EnvironmentTemplateTerraformModule $module, string $workspacePath): void
    {
        file_put_contents($workspacePath . '/main.tf', $module->main_tf);

        if (!empty($module->variables_tf)) {
            file_put_contents($workspacePath . '/variables.tf', $module->variables_tf);
        }

        if (!empty($module->outputs_tf)) {
            file_put_contents($workspacePath . '/outputs.tf', $module->outputs_tf);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tfvars building — fully driven by DB mapping
    // ─────────────────────────────────────────────────────────────────────────

    private function buildTfvars(Environment $environment, EnvironmentTemplateTerraformModule $module): array
    {
        $base    = ['environment_id' => (string) $environment->id];
        $mapping = $module->tfvars_mapping_json ?? [];

        if (empty($mapping)) {
            return $base;
        }

        return $base + $this->applyMapping($environment->configuration_json ?? [], $mapping);
    }

    /**
     * Applies tfvars_mapping_json to the environment configuration, respecting
     * optional type casts declared per field.
     *
     * Each entry value may be:
     *   - string  → Terraform variable name, value passed as-is
     *   - array   → { "tf_var": string, "cast": "int|float|bool|string|json" }
     */
    private function applyMapping(array $config, array $mapping): array
    {
        $tfvars = [];

        // ── environment_configuration fields ───────────────────────────────
        $expCfg      = $config['environment_configuration'] ?? [];
        $expMappings = $mapping['environment_configuration'] ?? [];

        foreach ($expMappings as $configField => $entry) {
            if (array_key_exists($configField, $expCfg)) {
                [$tfVar, $cast] = $this->parseEntry($entry);
                $tfvars[$tfVar] = $this->castValue($expCfg[$configField], $cast);
            }
        }

        // ── instance_configurations fields ────────────────────────────────
        $instCfgs     = $config['instance_configurations'] ?? [];
        $instMappings = $mapping['instance_configurations'] ?? [];

        foreach ($instMappings as $instanceKey => $fieldMappings) {
            $instance = $instCfgs[$instanceKey] ?? [];
            foreach ($fieldMappings as $configField => $entry) {
                if (array_key_exists($configField, $instance)) {
                    [$tfVar, $cast] = $this->parseEntry($entry);
                    $tfvars[$tfVar] = $this->castValue($instance[$configField], $cast);
                }
            }
        }

        return $tfvars;
    }

    /**
     * Parses a mapping entry into [tf_var_name, cast_type|null].
     *
     * Accepts:
     *   "variable_name"                               → ['variable_name', null]
     *   { "tf_var": "variable_name", "cast": "int" }  → ['variable_name', 'int']
     */
    private function parseEntry(mixed $entry): array
    {
        if (is_string($entry)) {
            return [$entry, null];
        }

        if (is_array($entry) && isset($entry['tf_var'])) {
            return [$entry['tf_var'], $entry['cast'] ?? null];
        }

        throw new \InvalidArgumentException(
            'Invalid tfvars_mapping_json entry. Expected a string or ' .
            '{"tf_var": "name", "cast": "int|float|bool|string|json"}. Got: ' .
            json_encode($entry),
        );
    }

    /**
     * Casts a value to the declared type.
     *
     * Supported casts: int, float, bool, string, json.
     * When cast is null the value is returned as-is (preserving the original type).
     */
    private function castValue(mixed $value, ?string $cast): mixed
    {
        return match ($cast) {
            'int'    => (int)    $value,
            'float'  => (float)  $value,
            'bool'   => (bool)   $value,
            'string' => (string) $value,
            'json'   => is_string($value) ? json_decode($value, true) : $value,
            default  => $value,
        };
    }
}
