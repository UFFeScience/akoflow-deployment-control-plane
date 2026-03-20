<?php

namespace App\Services;

use App\Models\Environment;
use App\Models\TerraformRun;
use App\Repositories\EnvironmentRepository;
use App\Repositories\TerraformRunRepository;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProvisionEnvironmentService
{
    public function __construct(
        private EnvironmentRepository              $environmentRepository,
        private TerraformRunRepository             $runRepository,
        private TerraformWorkspaceService          $workspaceService,
        private EnvironmentClusterProviderService  $providerResolver,
        private ProviderCredentialResolverService  $credentialResolver,
        private TerraformProcessRunnerService      $processRunner,
    ) {}

    public function handle(int $environmentId): TerraformRun
    {
        /** @var Environment $environment */
        $environment = $this->environmentRepository->find((string) $environmentId);

        if (!$environment) {
            throw new RuntimeException("Environment {$environmentId} not found.");
        }

        /** @var TerraformRun $run */
        $run = $this->runRepository->create([
            'environment_id' => $environment->id,
            'action'         => TerraformRun::ACTION_APPLY,
            'status'         => TerraformRun::STATUS_INITIALIZING,
            'started_at'     => now(),
        ]);

        try {
            // 1. Resolve the provider and its credentials from the DB
            $provider    = $this->providerResolver->resolve($environment);
            $credentials = $this->credentialResolver->resolve($provider);

            $run->appendLog("[akocloud] Provider: {$provider->name} (slug: {$provider->slug})");

            // 2. Build workspace files (main.tf, variables.tf, outputs.tf, tfvars.json)
            $workspace = $this->workspaceService->build($environment, $provider->slug);

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

            // 4. Capture outputs
            $outputJson = $this->readOutputJson($workspace['workspace_path']);

            if ($exitCode !== 0) {
                throw new RuntimeException("Terraform exited with code {$exitCode}.");
            }

            // 5. Mark success
            $run->update([
                'status'      => TerraformRun::STATUS_APPLIED,
                'output_json' => $outputJson,
                'finished_at' => now(),
            ]);

            $run->appendLog('[akocloud] Provisioning completed successfully.');

            $this->environmentRepository->update((string) $environment->id, ['status' => 'RUNNING']);

        } catch (Throwable $e) {
            $run->update([
                'status'      => TerraformRun::STATUS_FAILED,
                'finished_at' => now(),
            ]);
            $run->appendLog('[akocloud][ERROR] ' . $e->getMessage());

            $this->environmentRepository->update((string) $environment->id, ['status' => 'FAILED']);

            Log::error('ProvisionEnvironmentService failed', [
                'environment_id' => $environmentId,
                'run_id'         => $run->id,
                'error'          => $e->getMessage(),
            ]);
        }

        return $run->fresh();
    }

    private function readOutputJson(string $workspacePath): ?array
    {
        $outputFile = $workspacePath . '/outputs.json';

        if (!file_exists($outputFile)) {
            return null;
        }

        return json_decode(file_get_contents($outputFile), true);
    }
}
