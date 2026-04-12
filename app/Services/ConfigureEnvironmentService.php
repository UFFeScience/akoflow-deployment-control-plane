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

class ConfigureEnvironmentService
{
    public function __construct(
        private EnvironmentRepository                    $environmentRepository,
        private DeploymentRepository                     $deploymentRepository,
        private EnvironmentDeploymentProviderService     $providerResolver,
        private ProviderCredentialResolverService        $credentialResolver,
        private AnsiblePlaybookResolverService           $activityResolver,
        private AnsibleWorkspaceService                  $workspaceService,
        private AnsibleProcessRunnerService              $processRunner,
        private CreateAnsibleProvisionedResourcesService $createResources,
        private AnsiblePlaybookTaskHostStatusService     $taskHostStatusService,
    ) {}

    /**
     * Run all AnsiblePlaybook records with trigger=after_provision for the
     * deployment's provider, in dependency order.
     *
     * @return AnsiblePlaybookRun[]  One run record per executed activity.
     */
    public function handle(int $deploymentId): array
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

        $provider    = $this->providerResolver->resolveFromDeployment($deployment);
        $credentials = $this->credentialResolver->resolve($deployment);

        $playbooks = $this->activityResolver->resolve(
            (int) $environment->environment_template_version_id,
            $provider->type,
            AnsiblePlaybook::TRIGGER_AFTER_PROVISION,
        );

        if (empty($playbooks)) {
            // No playbooks configured — mark immediately as running
            $this->markEnvironmentRunning($deployment, $environment);
            return [];
        }

        $runs = [];

        foreach ($playbooks as $activity) {
            $run = $this->executeActivity(
                $activity,
                $deployment,
                $environment,
                $provider,
                $credentials,
            );
            $runs[] = $run;
        }

        // All playbooks succeeded — mark environment as running
        $this->markEnvironmentRunning($deployment, $environment);

        return $runs;
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function executeActivity(
        AnsiblePlaybook $activity,
        Deployment      $deployment,
        Environment     $environment,
        mixed           $provider,
        array           $credentials,
    ): AnsiblePlaybookRun {
        $run = $this->resolveRunForExecution($activity, $deployment, $provider);

        try {
            $run->appendLog("[akocloud] Activity: {$activity->name}");
            $run->appendLog("[akocloud] Provider: {$provider->name} (type: {$provider->type})");

            $run->update([
                'status'     => AnsiblePlaybookRun::STATUS_INITIALIZING,
                'started_at' => $run->started_at ?? now(),
            ]);

            $workspace = $this->workspaceService->buildForActivity($environment, $deployment, $provider->type, $activity);

            $run->update([
                'status'          => AnsiblePlaybookRun::STATUS_RUNNING,
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

            if ($outputJson) {
                $this->createResources->handle($deployment, $run->fresh());
                $run->appendLog('[akocloud] Provisioned resources created from activity output.');
            }

        } catch (Throwable $e) {
            $run->update([
                'status'      => AnsiblePlaybookRun::STATUS_FAILED,
                'finished_at' => now(),
            ]);

            $errorMessage = "Activity: {$activity->name} | Deployment: {$deployment->name} (id: {$deployment->id}) | {$e->getMessage()}";

            $run->appendLog('[akocloud][ERROR] ' . $errorMessage);

            $this->deploymentRepository->update((string) $deployment->id, [
                'status' => Deployment::STATUS_ERROR,
            ]);

            Log::error('[ConfigureEnvironmentService] ' . $errorMessage, ['exception' => $e]);

            throw $e;
        } finally {
            $this->taskHostStatusService->syncFromLogs($run->fresh());
        }

        return $run->fresh();
    }

    private function resolveRunForExecution(AnsiblePlaybook $activity, Deployment $deployment, mixed $provider): AnsiblePlaybookRun
    {
        $run = AnsiblePlaybookRun::query()
            ->where('deployment_id', $deployment->id)
            ->where('playbook_id', $activity->id)
            ->where('trigger', AnsiblePlaybook::TRIGGER_AFTER_PROVISION)
            ->where('status', AnsiblePlaybookRun::STATUS_QUEUED)
            ->whereNull('started_at')
            ->orderBy('id')
            ->first();

        if ($run) {
            return $run;
        }

        return AnsiblePlaybookRun::create([
            'deployment_id' => $deployment->id,
            'playbook_id'   => $activity->id,
            'playbook_name' => $activity->name,
            'trigger'       => AnsiblePlaybook::TRIGGER_AFTER_PROVISION,
            'status'        => AnsiblePlaybookRun::STATUS_QUEUED,
            'provider_type' => $provider->type,
            'triggered_by'  => 'system',
        ]);
    }

    private function markEnvironmentRunning(Deployment $deployment, Environment $environment): void
    {
        $this->deploymentRepository->update((string) $deployment->id, [
            'status' => Deployment::STATUS_RUNNING,
        ]);

        $this->environmentRepository->update((string) $environment->id, ['status' => 'RUNNING']);
    }
}
