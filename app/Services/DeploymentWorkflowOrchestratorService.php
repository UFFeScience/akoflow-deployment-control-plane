<?php

namespace App\Services;

use App\Enums\Messages;
use App\Models\Deployment;
use App\Models\Provider;
use App\Messaging\Contracts\MessageDispatcherInterface;
use App\Repositories\DeploymentRepository;

/**
 * Decides which deployment pipeline to start based on the provider type.
 *
 * Cloud providers (AWS, GCP, AZURE, CUSTOM):
 *   → ProvisionEnvironmentJob (Terraform) — Ansible follows automatically after success
 *
 * Local/pre-provisioned providers (HPC, ON_PREM):
 *   → ConfigureEnvironmentJob (Ansible only — no Terraform)
 */
class DeploymentWorkflowOrchestratorService
{
    private const ANSIBLE_ONLY_TYPES = ['HPC', 'ON_PREM'];

    public function __construct(
        private EnvironmentDeploymentProviderService $providerResolver,
        private DeploymentRepository                 $deploymentRepository,
        private MessageDispatcherInterface           $dispatcher,
    ) {}

    public function dispatch(Deployment $deployment): void
    {
        $provider = $this->providerResolver->resolveFromDeployment($deployment);

        if (in_array(strtoupper($provider->type), self::ANSIBLE_ONLY_TYPES, true)) {
            // Machine already exists — skip Terraform, go straight to Ansible
            $this->deploymentRepository->update((string) $deployment->id, [
                'status' => Deployment::STATUS_CONFIGURING,
            ]);

            $this->dispatcher->dispatch(Messages::CONFIGURE_ENVIRONMENT, [
                'deployment_id' => $deployment->id,
            ]);
        } else {
            // Cloud — provision infrastructure first; Ansible chained after Terraform success
            $this->deploymentRepository->update((string) $deployment->id, [
                'status' => Deployment::STATUS_PROVISIONING,
            ]);

            $this->dispatcher->dispatch(Messages::PROVISION_ENVIRONMENT, [
                'environment_id' => $deployment->environment_id,
                'deployment_id'  => $deployment->id,
            ]);
        }
    }
}
