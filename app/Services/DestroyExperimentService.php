<?php

namespace App\Services;

use App\Models\Experiment;
use App\Models\TerraformRun;
use App\Repositories\ExperimentRepository;
use App\Repositories\TerraformRunRepository;
use Illuminate\Support\Facades\Log;

class DestroyExperimentService
{
    public function __construct(
        private ExperimentRepository   $experimentRepository,
        private TerraformRunRepository $runRepository,
    ) {}

    public function handle(int $experimentId): ?TerraformRun
    {
        /** @var Experiment $experiment */
        $experiment = $this->experimentRepository->find((string) $experimentId);

        if (!$experiment) {
            throw new \RuntimeException("Experiment {$experimentId} not found.");
        }

        // Reuse the workspace path from the latest successful apply run
        $applyRun = $this->runRepository->latestForExperiment((string) $experimentId);

        if (!$applyRun || empty($applyRun->workspace_path)) {
            Log::warning('DestroyExperimentService: no apply run found.', [
                'experiment_id' => $experimentId,
            ]);
            return null;
        }

        /** @var TerraformRun $run */
        $run = $this->runRepository->create([
            'experiment_id'  => $experiment->id,
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

            $this->experimentRepository->update((string) $experiment->id, ['status' => 'STOPPED']);

        } catch (\Throwable $e) {
            $run->update([
                'status'      => TerraformRun::STATUS_FAILED,
                'finished_at' => now(),
            ]);
            $run->appendLog('[akocloud][ERROR] ' . $e->getMessage());

            Log::error('DestroyExperimentService failed', [
                'experiment_id' => $experimentId,
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
