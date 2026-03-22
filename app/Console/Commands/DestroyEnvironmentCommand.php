<?php

namespace App\Console\Commands;

use App\Models\TerraformRun;
use App\Services\DestroyEnvironmentService;
use Illuminate\Console\Command;

class DestroyEnvironmentCommand extends Command
{
    protected $signature = 'environment:destroy {environment_id : The ID of the environment to destroy} {--deployment_id= : The ID of the specific deployment to destroy}';

    protected $description = 'Destroy an environment by running terraform destroy via DestroyEnvironmentService';

    public function handle(DestroyEnvironmentService $service): int
    {
        $environmentId = (int) $this->argument('environment_id');
        $deploymentId  = $this->option('deployment_id') ? (int) $this->option('deployment_id') : null;

        $this->info("[akocloud] Destroying environment #{$environmentId}...");

        $run = $service->handle($environmentId, $deploymentId);

        if (!$run) {
            $this->error("[akocloud] No apply run found for environment #{$environmentId}. Nothing to destroy.");
            return self::FAILURE;
        }

        if ($run->status === TerraformRun::STATUS_DESTROYED) {
            $this->info("[akocloud] Infrastructure destroyed successfully. Run #{$run->id}");
            return self::SUCCESS;
        }

        $this->error("[akocloud] Destroy failed. Run #{$run->id} — status: {$run->status}");
        return self::FAILURE;
    }
}
