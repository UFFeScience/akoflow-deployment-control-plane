<?php

namespace App\Services;

use App\Repositories\DeploymentRepository;
use App\Models\Deployment;
use App\Models\DeploymentTemplate;
use Illuminate\Support\Str;

class CreateDeploymentService
{
    public function __construct(
        private DeploymentRepository $deployments,
    ) {}

    public function handle(string $environmentId, array $data): Deployment
    {
        $data['environment_id'] = $environmentId;

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

        return $deployment;
    }
}
