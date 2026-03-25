<?php

namespace App\Services;

use App\Repositories\DeploymentRepository;
use App\Models\Deployment;
use App\Models\DeploymentTemplate;
use App\Models\DeploymentProviderCredential;
use App\Models\Provider;
use Illuminate\Support\Str;

class CreateDeploymentService
{
    public function __construct(
        private DeploymentRepository $deployments,
    ) {}

    public function handle(string $environmentId, array $data): Deployment
    {
        $data['environment_id'] = $environmentId;

        // Extract multi-provider credentials — required
        $providerCredentials = $data['provider_credentials'];
        unset($data['provider_credentials']);

        if (empty($data['deployment_template_id'])) {
            $fallbackTemplateId = DeploymentTemplate::value('id');
            if ($fallbackTemplateId) {
                $data['deployment_template_id'] = $fallbackTemplateId;
            } else {
                unset($data['deployment_template_id']);
            }
        }

        $data['environment_type'] = $data['environment_type'] ?? Deployment::ENVIRONMENT_TYPES[0];
        $data['name'] = $data['name'] ?? 'deployment-' . Str::random(6);

        // Resources are created after terraform apply — not pre-created here
        unset($data['instance_groups'], $data['instances'], $data['resources']);

        /** @var Deployment $deployment */
        $deployment = $this->deployments->create($data);

        // Persist one pivot record per provider credential entry
        foreach ($providerCredentials as $entry) {
            $providerSlug = Provider::where('id', $entry['provider_id'])->value('slug');

            DeploymentProviderCredential::create([
                'deployment_id'          => $deployment->id,
                'provider_id'            => $entry['provider_id'],
                'provider_credential_id' => $entry['credential_id'] ?? null,
                'provider_slug'          => $providerSlug,
            ]);
        }

        return $deployment->load('providerCredentials');
    }
}
