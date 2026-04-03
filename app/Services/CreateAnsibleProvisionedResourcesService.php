<?php

namespace App\Services;

use App\Models\AnsibleRun;
use App\Models\Deployment;
use App\Models\EnvironmentTemplateAnsiblePlaybook;
use App\Models\ProvisionedResourceType;
use App\Repositories\ProvisionedResourceRepository;
use Illuminate\Support\Facades\Log;

/**
 * Maps ansible_outputs.json values to ProvisionedResource records using the
 * outputs_mapping_json defined on the EnvironmentTemplateAnsiblePlaybook.
 *
 * ── outputs_mapping_json format ──────────────────────────────────────────────
 * Identical schema to the Terraform equivalent, but output values are resolved
 * from a flat key→value map instead of the nested Terraform output format.
 *
 * {
 *   "resources": [
 *     {
 *       "name":                 "head-node",
 *       "ansible_resource_type":"hpc_node",
 *       "outputs": {
 *         "public_ip":            "head_node_public_ip",
 *         "private_ip":           "head_node_private_ip",
 *         "provider_resource_id": "head_node_hostname",
 *         "metadata": {
 *           "slurm_version": "slurm_version_installed",
 *           "akoflow_url":   "akoflow_dashboard_url"
 *         }
 *       }
 *     }
 *   ]
 * }
 *
 * ansible_outputs.json (flat format written by the playbook):
 * { "head_node_public_ip": "192.168.1.10", "slurm_version_installed": "23.11.4", ... }
 */
class CreateAnsibleProvisionedResourcesService
{
    public function __construct(
        private ProvisionedResourceRepository $resources,
    ) {}

    public function handle(Deployment $deployment, AnsibleRun $run): void
    {
        $outputJson = $run->output_json;

        if (empty($outputJson)) {
            Log::info('[CreateAnsibleProvisionedResourcesService] No output_json in run', [
                'deployment_id' => $deployment->id,
                'run_id'        => $run->id,
            ]);
            return;
        }

        // Load outputs_mapping_json from the playbook used for this run
        $playbook = $this->resolvePlaybook($deployment, $run);

        $resourceMappings = $playbook?->outputs_mapping_json['resources'] ?? [];

        if (empty($resourceMappings)) {
            Log::info('[CreateAnsibleProvisionedResourcesService] No outputs_mapping_json on playbook — skipping', [
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

    private function resolvePlaybook(Deployment $deployment, AnsibleRun $run): ?EnvironmentTemplateAnsiblePlaybook
    {
        $environment = $deployment->environment ?? $deployment->load('environment')->environment;

        return $environment
            ->templateVersion()
            ->with('ansiblePlaybooks')
            ->first()
            ?->ansiblePlaybooks
            ->firstWhere('provider_type', $run->provider_type);
    }

    private function createResource(Deployment $deployment, array $mapping, array $outputJson): void
    {
        $outputs            = $mapping['outputs'] ?? [];
        $ansibleResourceType = $mapping['ansible_resource_type'] ?? null;
        $name               = $mapping['name'] ?? null;

        $providerResourceId = isset($outputs['provider_resource_id'])
            ? ($outputJson[$outputs['provider_resource_id']] ?? null)
            : null;
        $publicIp           = isset($outputs['public_ip'])
            ? ($outputJson[$outputs['public_ip']] ?? null)
            : null;
        $privateIp          = isset($outputs['private_ip'])
            ? ($outputJson[$outputs['private_ip']] ?? null)
            : null;

        $metadata = [];
        foreach (($outputs['metadata'] ?? []) as $metaKey => $outputKey) {
            $value = $outputJson[$outputKey] ?? null;
            if ($value !== null) {
                $metadata[$metaKey] = $value;
            }
        }

        if (isset($outputs['iframe_url'])) {
            $iframeUrl = $outputJson[$outputs['iframe_url']] ?? null;
            if ($iframeUrl !== null) {
                $metadata['akoflow_iframe_url'] = $iframeUrl;
            }
        }

        $resourceType = $ansibleResourceType
            ? ProvisionedResourceType::where('provider_resource_identifier', $ansibleResourceType)->first()
            : null;

        $this->resources->updateOrCreateByDeploymentAndName(
            $deployment->id,
            $name,
            [
                'deployment_id'                => $deployment->id,
                'provisioned_resource_type_id' => $resourceType?->id,
                'provider_resource_id'         => $providerResourceId,
                'name'                         => $name,
                'status'                       => \App\Models\ProvisionedResource::STATUS_RUNNING,
                'public_ip'                    => $publicIp,
                'private_ip'                   => $privateIp,
                'metadata_json'                => !empty($metadata) ? $metadata : null,
            ],
        );
    }
}
