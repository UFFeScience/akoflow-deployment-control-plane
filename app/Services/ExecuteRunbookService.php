<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\Environment;
use App\Models\EnvironmentTemplateRunbook;
use App\Models\RunbookRun;
use App\Repositories\DeploymentRepository;
use App\Repositories\EnvironmentRepository;
use App\Repositories\RunbookRunRepository;
use RuntimeException;
use Throwable;

class ExecuteRunbookService
{
    public function __construct(
        private EnvironmentRepository                $environmentRepository,
        private DeploymentRepository                 $deploymentRepository,
        private RunbookRunRepository                 $runRepository,
        private EnvironmentDeploymentProviderService $providerResolver,
        private ProviderCredentialResolverService    $credentialResolver,
        private AnsibleWorkspaceService              $workspaceService,
        private AnsibleProcessRunnerService          $processRunner,
    ) {}

    public function handle(int $runbookId, int $deploymentId, string $triggeredBy = 'system', ?int $existingRunId = null): RunbookRun
    {
        /** @var EnvironmentTemplateRunbook $runbook */
        $runbook = EnvironmentTemplateRunbook::with('tasks')->findOrFail($runbookId);

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

        /** @var RunbookRun $run */
        $run = $existingRunId
            ? $this->runRepository->find((string) $existingRunId)
            : null;

        if (!$run) {
            $run = $this->runRepository->create([
                'deployment_id' => $deployment->id,
                'runbook_id'    => $runbook->id,
                'runbook_name'  => $runbook->name,
                'status'        => RunbookRun::STATUS_INITIALIZING,
                'triggered_by'  => $triggeredBy,
                'started_at'    => now(),
            ]);
        } else {
            $run->update([
                'status'     => RunbookRun::STATUS_INITIALIZING,
                'started_at' => now(),
            ]);
        }

        try {
            $provider    = $this->providerResolver->resolveFromDeployment($deployment);
            $credentials = $this->credentialResolver->resolve($deployment);

            $run->update([
                'provider_type' => $provider->type,
                'status'        => RunbookRun::STATUS_RUNNING,
            ]);

            $run->appendLog("[akocloud] Runbook: {$runbook->name}");
            $run->appendLog("[akocloud] Provider: {$provider->name} (type: {$provider->type})");

            // Build workspace from the runbook's own playbook_yaml
            $workspace = $this->workspaceService->buildForRunbook($environment, $deployment, $provider->type, $runbook);

            $run->update([
                'workspace_path'  => $workspace['workspace_path'],
                'extra_vars_json' => $workspace['extra_vars'],
                'inventory_ini'   => $workspace['inventory_ini'],
            ]);

            $run->appendLog("[akocloud] Workspace built at: {$workspace['workspace_path']}");

            $exitCode = $this->processRunner->run(
                $workspace['workspace_path'],
                $credentials,
                $run,
            );

            if ($exitCode !== 0) {
                throw new RuntimeException("ansible-playbook exited with code {$exitCode}.");
            }

            $outputJson = $this->processRunner->captureOutputs($workspace['workspace_path'], $run);

            $run->update([
                'status'      => RunbookRun::STATUS_COMPLETED,
                'output_json' => $outputJson,
                'finished_at' => now(),
            ]);

            $run->appendLog("[akocloud] Runbook '{$runbook->name}' completed successfully.");

        } catch (Throwable $e) {
            $run->update([
                'status'      => RunbookRun::STATUS_FAILED,
                'finished_at' => now(),
            ]);

            $run->appendLog("[akocloud][ERROR] Runbook failed: {$e->getMessage()}");
        }

        return $run->fresh();
    }
}
