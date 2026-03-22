<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\Environment;
use App\Models\TerraformRun;
use App\Repositories\DeploymentRepository;
use App\Repositories\EnvironmentRepository;
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
    ) {}

    public function handle(int $environmentId, ?int $deploymentId = null): ?TerraformRun
    {
        /** @var Environment $environment */
        $environment = $this->environmentRepository->find((string) $environmentId);

        if (!$environment) {
            throw new \RuntimeException("Environment {$environmentId} not found.");
        }

        // Reuse the workspace path from the latest successful apply run (contains tfstate)
        $applyRun = $this->runRepository->latestForEnvironment((string) $environmentId);

        if (!$applyRun || empty($applyRun->workspace_path)) {
            Log::warning('DestroyEnvironmentService: no apply run found.', [
                'environment_id' => $environmentId,
            ]);
            return null;
        }

        /** @var TerraformRun $run */
        $run = $this->runRepository->create([
            'environment_id'  => $environment->id,
            'action'          => TerraformRun::ACTION_DESTROY,
            'status'          => TerraformRun::STATUS_DESTROYING,
            'provider_type'   => $applyRun->provider_type,
            'workspace_path'  => $applyRun->workspace_path,
            'started_at'      => now(),
        ]);

        try {
            // Resolve fresh credentials from the deployment's configured credential
            $provider   = $this->providerResolver->resolve($environment);

            /** @var Deployment|null $deployment */
            $deployment = $deploymentId
                ? $this->deploymentRepository->find((string) $deploymentId)
                : $environment->deployments()->first();

            if (!$deployment) {
                throw new \RuntimeException(
                    "Environment [{$environment->id}] has no deployment associated."
                );
            }

            $credentials = $this->credentialResolver->resolve($deployment);

            $run->appendLog("[akocloud] Destroying workspace: {$applyRun->workspace_path}");
            $run->appendLog("[akocloud] Provider: {$provider->name} (slug: {$provider->slug})");

            $exitCode = $this->processRunner->run(
                $applyRun->workspace_path,
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

            $this->environmentRepository->update((string) $environment->id, ['status' => 'STOPPED']);

        } catch (\Throwable $e) {
            $run->update([
                'status'      => TerraformRun::STATUS_FAILED,
                'finished_at' => now(),
            ]);
            $run->appendLog('[akocloud][ERROR] ' . $e->getMessage());

            Log::error('DestroyEnvironmentService failed', [
                'environment_id' => $environmentId,
                'run_id'         => $run->id,
                'error'          => $e->getMessage(),
            ]);
        }

        return $run->fresh();
    }
}
