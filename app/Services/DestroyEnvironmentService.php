<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\Environment;
use App\Models\TerraformRun;
use App\Repositories\DeploymentRepository;
use App\Repositories\EnvironmentRepository;
use App\Repositories\ProvisionedResourceRepository;
use App\Repositories\TerraformRunRepository;
use Illuminate\Support\Facades\Log;

class DestroyEnvironmentService
{
    public function __construct(
        private EnvironmentRepository                $environmentRepository,
        private TerraformRunRepository               $runRepository,
        private DeploymentRepository                 $deploymentRepository,
        private EnvironmentDeploymentProviderService $providerResolver,
        private ProviderCredentialResolverService    $credentialResolver,
        private TerraformProcessRunnerService        $processRunner,
        private ProvisionedResourceRepository        $provisionedResources,
    ) {}

    public function handle(int $environmentId, ?int $deploymentId = null): ?TerraformRun
    {
        /** @var Environment $environment */
        $environment = $this->environmentRepository->find((string) $environmentId);

        if (!$environment) {
            throw new \RuntimeException("Environment {$environmentId} not found.");
        }

        // Reuse the latest apply run — tfstate lives in its workspace and we append logs to it
        $run = $this->runRepository->latestForEnvironment((string) $environmentId);

        if (!$run || empty($run->workspace_path)) {
            Log::warning('DestroyEnvironmentService: no apply run found.', [
                'environment_id' => $environmentId,
            ]);
            return null;
        }

        // Find the deployment before mutating the run so we can update its status
        /** @var Deployment|null $deployment */
        $deployment = $deploymentId
            ? $this->deploymentRepository->find((string) $deploymentId)
            : $environment->deployments()->latest()->first();

        if (!$deployment) {
            throw new \RuntimeException(
                "Environment [{$environment->id}] has no deployment associated."
            );
        }

        // Mark the existing run as DESTROYING (append — don't wipe the apply logs)
        $run->update([
            'action'      => TerraformRun::ACTION_DESTROY,
            'status'      => TerraformRun::STATUS_DESTROYING,
            'finished_at' => null,
        ]);

        $run->appendLog("\n[akocloud] ──────────── DESTROY ────────────");

        try {
            $provider    = $this->providerResolver->resolve($environment);
            $credentials = $this->credentialResolver->resolve($deployment);

            $run->appendLog("[akocloud] Destroying workspace: {$run->workspace_path}");
            $run->appendLog("[akocloud] Provider: {$provider->name} (slug: {$provider->slug})");

            $exitCode = $this->processRunner->run(
                $run->workspace_path,
                TerraformRun::ACTION_DESTROY,
                $credentials,
                $run,
            );

            if ($exitCode !== 0) {
                throw new \RuntimeException("Terraform destroy exited with code {$exitCode}.");
            }

            $run->update([
                'status'      => TerraformRun::STATUS_DESTROYED,
                'finished_at' => now(),
            ]);

            $run->appendLog('[akocloud] Infrastructure destroyed successfully.');

            // Mark every provisioned resource for this deployment as DESTROYED
            $this->provisionedResources->markAllDestroyedForDeployment($deployment->id);
            $run->appendLog('[akocloud] Provisioned resources marked as DESTROYED.');

            $this->environmentRepository->update((string) $environment->id, ['status' => 'STOPPED']);

            $this->deploymentRepository->update((string) $deployment->id, [
                'status' => Deployment::STATUS_STOPPED,
            ]);

        } catch (\Throwable $e) {
            $run->update([
                'status'      => TerraformRun::STATUS_FAILED,
                'finished_at' => now(),
            ]);
            $run->appendLog('[akocloud][ERROR] ' . $e->getMessage());

            $this->deploymentRepository->update((string) $deployment->id, [
                'status' => Deployment::STATUS_ERROR,
            ]);

            Log::error('DestroyEnvironmentService failed', [
                'environment_id' => $environmentId,
                'deployment_id'  => $deployment->id,
                'run_id'         => $run->id,
                'error'          => $e->getMessage(),
            ]);
        }

        return $run->fresh();
    }
}
