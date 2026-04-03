<?php

namespace App\Services;

use App\Enums\Messages;
use App\Models\Deployment;
use App\Models\Environment;
use App\Models\TerraformRun;
use App\Messaging\Contracts\MessageDispatcherInterface;
use App\Repositories\DeploymentRepository;
use App\Repositories\EnvironmentRepository;
use App\Repositories\TerraformRunRepository;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProvisionEnvironmentService
{
    public function __construct(
        private EnvironmentRepository                $environmentRepository,
        private TerraformRunRepository               $runRepository,
        private DeploymentRepository                 $deploymentRepository,
        private TerraformWorkspaceService            $workspaceService,
        private EnvironmentDeploymentProviderService $providerResolver,
        private ProviderCredentialResolverService    $credentialResolver,
        private TerraformProcessRunnerService        $processRunner,
        private CreateProvisionedResourcesService    $createResources,
        private MessageDispatcherInterface           $dispatcher,
    ) {}

    public function handle(int $environmentId, int $deploymentId): TerraformRun
    {
        /** @var Environment $environment */
        $environment = $this->environmentRepository->find((string) $environmentId);

        if (!$environment) {
            throw new RuntimeException("Environment {$environmentId} not found.");
        }

        /** @var Deployment $deployment */
        $deployment = $this->deploymentRepository->find((string) $deploymentId);

        if (!$deployment) {
            throw new RuntimeException("Deployment {$deploymentId} not found.");
        }

        /** @var TerraformRun $run */
        $run = $this->runRepository->create([
            'environment_id' => $environment->id,
            'action'         => TerraformRun::ACTION_APPLY,
            'status'         => TerraformRun::STATUS_INITIALIZING,
            'started_at'     => now(),
        ]);

        try {
            // 1. Resolve the provider and its credentials from the specific deployment
            $provider    = $this->providerResolver->resolveFromDeployment($deployment);
            $credentials = $this->credentialResolver->resolve($deployment);
 
            $run->appendLog("[akocloud] Provider: {$provider->name} (slug: {$provider->slug})");

            // 2. Build workspace files (main.tf, variables.tf, outputs.tf, tfvars.json)
            $workspace = $this->workspaceService->build($environment, strtoupper($provider->type));

            $run->update([
                'provider_type'  => $workspace['provider_type'],
                'workspace_path' => $workspace['workspace_path'],
                'tfvars_json'    => $workspace['tfvars'],
                'status'         => TerraformRun::STATUS_PLANNING,
            ]);

            $run->appendLog("[akocloud] Workspace built at: {$workspace['workspace_path']}");

            // 3. Run terraform init + apply, injecting credentials as process env vars
            $run->update(['status' => TerraformRun::STATUS_APPLYING]);

            $exitCode = $this->processRunner->run(
                $workspace['workspace_path'],
                TerraformRun::ACTION_APPLY,
                $credentials,
                $run,
            );

            // 4. Check exit code
            if ($exitCode !== 0) {
                throw new RuntimeException("Terraform exited with code {$exitCode}.");
            }

            // 5. Capture outputs via `terraform output -json`
            $outputJson = $this->processRunner->captureOutputs(
                $workspace['workspace_path'],
                $credentials,
                $run,
            );

            // 6. Mark success
            $run->update([
                'status'      => TerraformRun::STATUS_APPLIED,
                'output_json' => $outputJson,
                'finished_at' => now(),
            ]);

            $run->appendLog('[akocloud] Provisioning completed successfully.');

            // 7. Create ProvisionedResource records from terraform outputs
            $this->createResources->handle($deployment, $run->fresh());
            $run->appendLog('[akocloud] Provisioned resources created from output_json.');

            // 8. Chain the Ansible configuration phase
            $this->deploymentRepository->update((string) $deployment->id, [
                'status' => Deployment::STATUS_CONFIGURING,
            ]);

            $this->dispatcher->dispatch(Messages::CONFIGURE_ENVIRONMENT, [
                'deployment_id' => $deployment->id,
            ]);

            $run->appendLog('[akocloud] Configuration job queued — Ansible will run next.');

        } catch (Throwable $e) {
            $run->update([
                'status'      => TerraformRun::STATUS_FAILED,
                'finished_at' => now(),
            ]);

            $errorMessage = "Environment: {$environment->name} (id: {$environment->id})"
                . " | Deployment: {$deployment->name} (id: {$deployment->id})"
                . " | {$e->getMessage()}";

            $run->appendLog('[akocloud][ERROR] ' . $errorMessage);

            $this->deploymentRepository->update((string) $deployment->id, [
                'status' => Deployment::STATUS_ERROR,
            ]);

            $this->environmentRepository->update((string) $environment->id, ['status' => 'FAILED']);

            Log::error('ProvisionEnvironmentService failed', [
                'environment_id' => $environmentId,
                'deployment_id'  => $deployment?->id,
                'run_id'         => $run->id,
                'error'          => $e->getMessage(),
            ]);
        }

        return $run->fresh();
    }
}
