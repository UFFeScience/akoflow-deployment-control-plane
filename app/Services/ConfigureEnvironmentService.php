<?php

namespace App\Services;

use App\Models\AnsibleRun;
use App\Models\Deployment;
use App\Models\Environment;
use App\Repositories\AnsibleRunRepository;
use App\Repositories\DeploymentRepository;
use App\Repositories\EnvironmentRepository;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ConfigureEnvironmentService
{
    public function __construct(
        private EnvironmentRepository                    $environmentRepository,
        private DeploymentRepository                     $deploymentRepository,
        private AnsibleRunRepository                     $runRepository,
        private EnvironmentDeploymentProviderService     $providerResolver,
        private ProviderCredentialResolverService        $credentialResolver,
        private AnsibleWorkspaceService                  $workspaceService,
        private AnsibleProcessRunnerService              $processRunner,
        private CreateAnsibleProvisionedResourcesService $createResources,
    ) {}

    public function handle(int $deploymentId): AnsibleRun
    {
        /** @var Deployment $deployment */
        $deployment = $this->deploymentRepository->find((string) $deploymentId);

        if (!$deployment) {
            throw new RuntimeException("Deployment {$deploymentId} not found.");
        }

        /** @var Environment $environment */
        $environment = $this->environmentRepository->find((string) $deployment->environment_id);

        if (!$environment) {
            throw new RuntimeException("Environment {$deployment->environment_id} not found.");
        }

        /** @var AnsibleRun $run */
        $run = $this->runRepository->create([
            'deployment_id' => $deployment->id,
            'action'        => AnsibleRun::ACTION_CONFIGURE,
            'status'        => AnsibleRun::STATUS_INITIALIZING,
            'started_at'    => now(),
        ]);

        try {
            // 1. Resolve provider and credentials from the deployment
            $provider    = $this->providerResolver->resolveFromDeployment($deployment);
            $credentials = $this->credentialResolver->resolve($deployment);

            $run->appendLog("[akocloud] Provider: {$provider->name} (type: {$provider->type})");

            $run->update([
                'provider_type' => $provider->type,
                'status'        => AnsibleRun::STATUS_RUNNING,
            ]);

            // 2. Build workspace files (playbook.yml, inventory.ini, extra_vars.json)
            $workspace = $this->workspaceService->build($environment, $deployment, $provider->type);

            $run->update([
                'workspace_path' => $workspace['workspace_path'],
                'extra_vars_json' => $workspace['extra_vars'],
                'inventory_ini'  => $workspace['inventory_ini'],
            ]);

            $run->appendLog("[akocloud] Workspace built at: {$workspace['workspace_path']}");

            // 3. Run ansible-galaxy + ansible-playbook, injecting credentials as process env vars
            $exitCode = $this->processRunner->run(
                $workspace['workspace_path'],
                $credentials,
                $run,
            );

            // 4. Check exit code
            if ($exitCode !== 0) {
                throw new RuntimeException("ansible-playbook exited with code {$exitCode}.");
            }

            // 5. Capture outputs from ansible_outputs.json written by the playbook
            $outputJson = $this->processRunner->captureOutputs(
                $workspace['workspace_path'],
                $run,
            );

            // 6. Mark success
            $run->update([
                'status'      => AnsibleRun::STATUS_COMPLETED,
                'output_json' => $outputJson,
                'finished_at' => now(),
            ]);

            $run->appendLog('[akocloud] Configuration completed successfully.');

            // 7. Create ProvisionedResource records from ansible outputs
            if ($outputJson) {
                $this->createResources->handle($deployment, $run->fresh());
                $run->appendLog('[akocloud] Provisioned resources created from ansible output.');
            }

            $this->deploymentRepository->update((string) $deployment->id, [
                'status' => Deployment::STATUS_RUNNING,
            ]);

            $this->environmentRepository->update((string) $environment->id, ['status' => 'RUNNING']);

        } catch (Throwable $e) {
            $run->update([
                'status'      => AnsibleRun::STATUS_FAILED,
                'finished_at' => now(),
            ]);

            $errorMessage = "Deployment: {$deployment->name} (id: {$deployment->id})"
                . " | {$e->getMessage()}";

            $run->appendLog('[akocloud][ERROR] ' . $errorMessage);

            $this->deploymentRepository->update((string) $deployment->id, [
                'status' => Deployment::STATUS_ERROR,
            ]);

            Log::error('[ConfigureEnvironmentService] ' . $errorMessage, [
                'exception' => $e,
            ]);

            throw $e;
        }

        return $run;
    }
}
