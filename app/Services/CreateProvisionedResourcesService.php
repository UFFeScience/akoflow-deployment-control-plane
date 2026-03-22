<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\ProvisionedResource;
use App\Models\ProvisionedResourceType;
use App\Models\TerraformRun;
use App\Repositories\ProvisionedResourceRepository;
use Illuminate\Support\Facades\Log;

/**
 * Reads the `outputs_mapping_json` defined on the TerraformModule and maps
 * the captured `terraform output -json` values to ProvisionedResource records.
 *
 * ── outputs_mapping_json format ──────────────────────────────────────────────
 * {
 *   "resources": [
 *     {
 *       "name":          "nginx-vm",               // static label stored in the DB record
 *       "terraform_type":"aws_instance",            // optional — used to resolve ProvisionedResourceType
 *       "outputs": {
 *         "provider_resource_id": "instance_id",   // Terraform output key → provider resource ID
 *         "public_ip":            "public_ip",      // Terraform output key → public IP field
 *         "private_ip":           "private_ip",     // Terraform output key → private IP field
 *         "metadata": {
 *           "nginx_url":          "nginx_url",      // arbitrary key → Terraform output key
 *           "security_group_id":  "security_group_id"
 *         }
 *       }
 *     }
 *   ]
 * }
 *
 * Terraform output JSON structure (from `terraform output -json`):
 * { "output_name": { "value": "actual_value", "type": "string" }, ... }
 */
class CreateProvisionedResourcesService
{
    public function __construct(
        private ProvisionedResourceRepository $resources,
    ) {}

    public function handle(Deployment $deployment, TerraformRun $run): void
    {
        $outputJson = $run->output_json;

        if (empty($outputJson)) {
            Log::info('[CreateProvisionedResourcesService] No output_json in run', [
                'deployment_id' => $deployment->id,
                'run_id'        => $run->id,
            ]);
            return;
        }

        // Load outputs_mapping_json from the module used for this run
        $module = $run->environment
            ->templateVersion()
            ->with('terraformModules')
            ->first()
            ?->terraformModules
            ->firstWhere('provider_type', $run->provider_type);

        $resourceMappings = $module?->outputs_mapping_json['resources'] ?? [];

        if (empty($resourceMappings)) {
            Log::info('[CreateProvisionedResourcesService] No outputs_mapping_json on module — skipping', [
                'deployment_id' => $deployment->id,
                'run_id'        => $run->id,
                'provider_type' => $run->provider_type,
            ]);
            return;
        }

        foreach ($resourceMappings as $mapping) {
            $this->createResource($deployment, $mapping, $outputJson);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function createResource(Deployment $deployment, array $mapping, array $outputJson): void
    {
        $outputs = $mapping['outputs'] ?? [];

        $terraformType      = $mapping['terraform_type'] ?? null;
        $name               = $mapping['name'] ?? null;
        $providerResourceId = isset($outputs['provider_resource_id'])
            ? $this->resolveOutput($outputJson, $outputs['provider_resource_id'])
            : null;
        $publicIp           = isset($outputs['public_ip'])
            ? $this->resolveOutput($outputJson, $outputs['public_ip'])
            : null;
        $privateIp          = isset($outputs['private_ip'])
            ? $this->resolveOutput($outputJson, $outputs['private_ip'])
            : null;

        $metadata = [];
        foreach (($outputs['metadata'] ?? []) as $metaKey => $outputKey) {
            $value = $this->resolveOutput($outputJson, $outputKey);
            if ($value !== null) {
                $metadata[$metaKey] = $value;
            }
        }

        // Special key: iframe_url → stored in metadata_json as 'akoflow_iframe_url'
        if (isset($outputs['iframe_url'])) {
            $iframeUrl = $this->resolveOutput($outputJson, $outputs['iframe_url']);
            if ($iframeUrl !== null) {
                $metadata['akoflow_iframe_url'] = $iframeUrl;
            }
        }

        $resourceType = $terraformType
            ? ProvisionedResourceType::where('provider_resource_identifier', $terraformType)->first()
            : null;

        $this->resources->create([
            'deployment_id'                => $deployment->id,
            'provisioned_resource_type_id' => $resourceType?->id,
            'provider_resource_id'         => $providerResourceId,
            'name'                         => $name,
            'status'                       => ProvisionedResource::STATUS_RUNNING,
            'public_ip'                    => $publicIp,
            'private_ip'                   => $privateIp,
            'metadata_json'                => !empty($metadata) ? $metadata : null,
        ]);
    }

    /**
     * Resolves a single value from the `terraform output -json` structure.
     *
     * @param  array<string, array{value: mixed}>  $outputJson
     */
    private function resolveOutput(array $outputJson, string $key): ?string
    {
        return isset($outputJson[$key]['value']) ? (string) $outputJson[$key]['value'] : null;
    }
}
