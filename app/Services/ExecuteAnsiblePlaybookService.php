<?php

namespace App\Services;

use App\Models\AnsiblePlaybook;
use App\Models\AnsiblePlaybookRun;
use App\Models\Deployment;
use App\Models\Environment;
use App\Repositories\DeploymentRepository;
use App\Repositories\EnvironmentRepository;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Executes a single AnsiblePlaybook on demand (trigger = manual or when_ready).
 *
 * Used by ExecuteAnsiblePlaybookJob. The caller may supply an existing
 * AnsiblePlaybookRun id (pre-created as QUEUED by TriggerAnsiblePlaybookService)
 * so the UI can show the queued state before the job actually starts.
 */
class ExecuteAnsiblePlaybookService
{
    public function __construct(
        private EnvironmentRepository                $environmentRepository,
        private DeploymentRepository                 $deploymentRepository,
        private EnvironmentDeploymentProviderService $providerResolver,
        private ProviderCredentialResolverService    $credentialResolver,
        private AnsibleWorkspaceService              $workspaceService,
        private AnsibleProcessRunnerService          $processRunner,
        private AnsiblePlaybookTaskHostStatusService $taskHostStatusService,
    ) {}

    public function handle(
        int     $playbookId,
        int     $deploymentId,
        string  $triggeredBy = 'system',
        ?int    $existingRunId = null,
    ): AnsiblePlaybookRun {
        /** @var AnsiblePlaybook $activity */
        $activity = AnsiblePlaybook::findOrFail($playbookId);

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

        /** @var AnsiblePlaybookRun $run */
        $run = $existingRunId ? AnsiblePlaybookRun::find($existingRunId) : null;

        if (!$run) {
            $run = AnsiblePlaybookRun::create([
                'deployment_id' => $deployment->id,
                'playbook_id'   => $activity->id,
                'playbook_name' => $activity->name,
                'trigger'       => $activity->trigger,
                'status'        => AnsiblePlaybookRun::STATUS_INITIALIZING,
                'triggered_by'  => $triggeredBy,
                'started_at'    => now(),
            ]);
        } else {
            $run->update([
                'status'     => AnsiblePlaybookRun::STATUS_INITIALIZING,
                'started_at' => now(),
            ]);
        }

        try {
            $provider    = $this->providerResolver->resolveFromDeployment($deployment);
            $credentials = $this->credentialResolver->resolve($deployment);

            $run->update([
                'provider_type' => $provider->type,
                'status'        => AnsiblePlaybookRun::STATUS_RUNNING,
            ]);

            $run->appendLog("[akocloud] Activity: {$activity->name}");
            $run->appendLog("[akocloud] Provider: {$provider->name} (type: {$provider->type})");

            $workspace = $this->workspaceService->buildForActivity($environment, $deployment, $provider->type, $activity);

            $run->update([
                'workspace_path'  => $workspace['workspace_path'],
                'extra_vars_json' => $workspace['extra_vars'],
                'inventory_ini'   => $workspace['inventory_ini'],
            ]);

            $this->taskHostStatusService->initializePending($run->fresh());

            $run->appendLog("[akocloud] Workspace built at: {$workspace['workspace_path']}");

            $exitCode = $this->processRunner->run(
                $workspace['workspace_path'],
                $credentials,
                $run,
                fn (string $line) => $this->taskHostStatusService->consumeLogLine($run, $line),
            );

            if ($exitCode !== 0) {
                throw new RuntimeException("ansible-playbook exited with code {$exitCode}.");
            }

            $outputJson = $this->processRunner->captureOutputs($workspace['workspace_path'], $run);

            $run->update([
                'status'      => AnsiblePlaybookRun::STATUS_COMPLETED,
                'output_json' => $outputJson,
                'finished_at' => now(),
            ]);

            $run->appendLog("[akocloud] Activity '{$activity->name}' completed successfully.");

        } catch (Throwable $e) {
            $run->update([
                'status'      => AnsiblePlaybookRun::STATUS_FAILED,
                'finished_at' => now(),
            ]);

            $run->appendLog("[akocloud][ERROR] Activity failed: {$e->getMessage()}");

            Log::error('[ExecuteAnsiblePlaybookService] Activity failed', [
                'playbook_id'   => $activity->id,
                'playbook_name' => $activity->name,
                'deployment_id' => $deploymentId,
                'error'         => $e->getMessage(),
            ]);
        }

        $this->taskHostStatusService->syncFromLogs($run->fresh());

        return $run->fresh()->load('taskHostStatuses');
    }
}
