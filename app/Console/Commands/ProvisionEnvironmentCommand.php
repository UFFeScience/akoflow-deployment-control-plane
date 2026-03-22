<?php

namespace App\Console\Commands;

use App\Models\TerraformRun;
use App\Services\ProvisionEnvironmentService;
use Illuminate\Console\Command;

class ProvisionEnvironmentCommand extends Command
{
    protected $signature = 'environment:provision {environment_id : The ID of the environment to provision} {--deployment_id= : The ID of the specific deployment to provision}';

    protected $description = 'Provision an environment by running terraform apply via ProvisionEnvironmentService';

    public function handle(ProvisionEnvironmentService $service): int
    {
        $environmentId = (int) $this->argument('environment_id');
        $deploymentId  = $this->option('deployment_id') ? (int) $this->option('deployment_id') : null;

        $this->info("[akocloud] Provisioning environment #{$environmentId}...");

        $run = $service->handle($environmentId, $deploymentId);

        if ($run->status === TerraformRun::STATUS_APPLIED) {
            $this->info("[akocloud] Provisioning completed successfully. Run #{$run->id}");
            return self::SUCCESS;
        }

        $this->error("[akocloud] Provisioning failed. Run #{$run->id} — status: {$run->status}");
        return self::FAILURE;
    }
}
