<?php

namespace App\Services;

use App\Models\Environment;
use App\Models\TerraformRun;
use App\Repositories\EnvironmentRepository;
use App\Repositories\TerraformRunRepository;
use Illuminate\Support\Facades\Log;

class DestroyEnvironmentService
{
    public function __construct(
        private EnvironmentRepository   $environmentRepository,
        private TerraformRunRepository $runRepository,
    ) {}

    public function handle(int $environmentId): ?TerraformRun
    {
        /** @var Environment $environment */
        $environment = $this->environmentRepository->find((string) $environmentId);

        if (!$environment) {
            throw new \RuntimeException("Environment {$environmentId} not found.");
        }

        // Reuse the workspace path from the latest successful apply run
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
            'action'         => TerraformRun::ACTION_DESTROY,
            'status'         => TerraformRun::STATUS_DESTROYING,
            'provider_type'  => $applyRun->provider_type,
            'workspace_path' => $applyRun->workspace_path,
            'started_at'     => now(),
        ]);

        try {
            $scriptPath    = base_path('infra/terraform/scripts/run.sh');
            $workspacePath = $applyRun->workspace_path;

            $command = sprintf(
                'bash %s destroy %s 2>&1',
                escapeshellarg($scriptPath),
                escapeshellarg($workspacePath),
            );

            $run->appendLog("[akocloud] Destroying: {$command}");

            $returnCode = $this->streamProcess($command, $run);

            if ($returnCode !== 0) {
                throw new \RuntimeException("Terraform destroy exited with code {$returnCode}.");
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
                'run_id'        => $run->id,
                'error'         => $e->getMessage(),
            ]);
        }

        return $run->fresh();
    }

    private function streamProcess(string $command, TerraformRun $run): int
    {
        $proc = popen($command, 'r');

        if ($proc === false) {
            throw new \RuntimeException('Failed to open Terraform destroy process.');
        }

        while (!feof($proc)) {
            $line = fgets($proc, 4096);
            if ($line !== false) {
                $run->appendLog(rtrim($line));
            }
        }

        return pclose($proc);
    }
}
