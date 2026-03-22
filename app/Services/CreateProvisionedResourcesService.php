<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\ProvisionedResource;
use App\Models\ProvisionedResourceType;
use App\Models\TerraformRun;
use App\Repositories\ProvisionedResourceRepository;
use Illuminate\Support\Facades\Log;

/**
 * Parses terraform output_json after a successful apply and persists
 * ProvisionedResource records for the deployment.
 *
 * Expected output_json format (from `terraform output -json`):
 * {
 *   "resources": {
 *     "value": [
 *       {
 *         "provider_resource_id": "i-0abc123",
 *         "terraform_type":       "aws_instance",
 *         "name":                 "web-1",
 *         "public_ip":            "1.2.3.4",   // optional
 *         "private_ip":           "10.0.0.5",  // optional
 *         "metadata":             {}            // optional
 *       }
 *     ]
 *   }
 * }
 *
 * If the key "resources" is absent, the service is a no-op (graceful).
 */
class CreateProvisionedResourcesService
{
    public function __construct(
        private ProvisionedResourceRepository $resources,
    ) {}

    public function handle(Deployment $deployment, TerraformRun $run): void
    {
        $outputJson = $run->output_json;

        if (empty($outputJson) || !isset($outputJson['resources']['value'])) {
            Log::info('[CreateProvisionedResourcesService] No resources key in output_json', [
                'deployment_id' => $deployment->id,
                'run_id'        => $run->id,
            ]);
            return;
        }

        $rawResources = $outputJson['resources']['value'];

        if (!is_array($rawResources) || empty($rawResources)) {
            return;
        }

        foreach ($rawResources as $raw) {
            $this->createResource($deployment, $raw);
        }
    }

    private function createResource(Deployment $deployment, array $raw): void
    {
        $terraformType      = $raw['terraform_type'] ?? null;
        $providerResourceId = $raw['provider_resource_id'] ?? null;
        $name               = $raw['name'] ?? null;
        $publicIp           = $raw['public_ip'] ?? null;
        $privateIp          = $raw['private_ip'] ?? null;
        $metadata           = $raw['metadata'] ?? null;

        // Resolve the resource type from the terraform identifier
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
            'metadata_json'                => is_array($metadata) ? $metadata : null,
        ]);
    }
}
