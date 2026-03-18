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
        private EnvironmentRepository      $environmentRepository,
        private TerraformRunRepository    $runRepository,
        private TerraformWorkspaceService $workspaceService,
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
            'action'        => TerraformRun::ACTION_APPLY,
            'status'        => TerraformRun::STATUS_INITIALIZING,
            'started_at'    => now(),
        ]);

        try {
            // 1. Generate workspace files (main.tf + tfvars + .env)
            $workspace = $this->workspaceService->build($environment);

            $run->update([
                'provider_type'  => $workspace['provider_type'],
                'workspace_path' => $workspace['workspace_path'],
                'tfvars_json'    => $workspace['tfvars'],
                'status'         => TerraformRun::STATUS_PLANNING,
            ]);

            $run->appendLog("[akocloud] Workspace built at: {$workspace['workspace_path']}");
            $run->appendLog("[akocloud] Provider: {$workspace['provider_type']}");

            // 2. Execute Terraform apply
            $run->update(['status' => TerraformRun::STATUS_APPLYING]);

            $scriptPath    = base_path('infra/terraform/scripts/run.sh');
            $workspacePath = $workspace['workspace_path'];

            $command = sprintf(
                'bash %s apply %s 2>&1',
                escapeshellarg($scriptPath),
                escapeshellarg($workspacePath),
            );

            $run->appendLog("[akocloud] Running: {$command}");

            $returnCode = $this->streamProcess($command, $run);

            // 3. Parse outputs file
            $outputJson = $this->readOutputJson($workspacePath);

            if ($returnCode !== 0) {
                throw new RuntimeException("Terraform exited with code {$returnCode}.");
            }

            // 4. Mark success
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
                'run_id'        => $run->id,
                'error'         => $e->getMessage(),
            ]);
        }

        return $run->fresh();
    }

    /**
     * Opens a process, streams its output line by line into the run log,
     * and returns the exit code.
     */
    private function streamProcess(string $command, TerraformRun $run): int
    {
        $proc = popen($command, 'r');

        if ($proc === false) {
            throw new RuntimeException('Failed to open Terraform process.');
        }

        while (!feof($proc)) {
            $line = fgets($proc, 4096);
            if ($line !== false) {
                $run->appendLog(rtrim($line));
            }
        }

        return pclose($proc);
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
