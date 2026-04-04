<?php

namespace App\Console\Commands;

use App\Enums\HealthStatus;
use App\Services\RunProviderHealthCheckSweepService;
use Illuminate\Console\Command;

class ProviderHealthCheckLoopCommand extends Command
{
    protected $signature = 'provider:health-check-loop
                            {--interval=60 : Seconds to wait between each full sweep}';

    protected $description = 'Continuous loop that checks the health of every active provider and updates its status.';

    public function handle(RunProviderHealthCheckSweepService $sweepService): int
    {
        $interval = (int) $this->option('interval');

        $this->info("[akocloud] Provider health-check loop started (interval: {$interval}s).");

        do {
            $this->runSweep($sweepService);

            if ($interval > 0) {
                $this->info("[akocloud] Sweep complete. Sleeping {$interval}s…");
                sleep($interval);
            }
        } while ($interval > 0);

        return self::SUCCESS;
    }

    private function runSweep(RunProviderHealthCheckSweepService $sweepService): void
    {
        $results = $sweepService->execute();

        if (empty($results)) {
            $this->line('[akocloud] No credentials found, skipping sweep.');
            return;
        }

        foreach ($results as $result) {
            $credential = $result['credential'];
            $provider   = $credential->provider;
            $status     = $result['status'];
            $icon       = $status === HealthStatus::HEALTHY->value ? '✓' : '✗';
            $label      = "{$provider?->slug} / {$credential->slug}";

            if ($result['exception'] !== null) {
                $this->error("[{$icon}] #{$credential->id} ({$label}) → {$status}: {$result['exception']}");
            } else {
                $this->line("[{$icon}] #{$credential->id} ({$label}) → {$status}");
            }
        }
    }
}
