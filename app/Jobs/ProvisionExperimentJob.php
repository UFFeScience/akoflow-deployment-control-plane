<?php

namespace App\Jobs;

use App\Services\ProvisionExperimentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProvisionExperimentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maximum wall-clock seconds allowed for the Terraform run */
    public int $timeout = 3600;

    /** Retries on unexpected failure */
    public int $tries = 1;

    public function __construct(
        public readonly int $experimentId,
    ) {}

    public function handle(ProvisionExperimentService $service): void
    {
        $service->handle($this->experimentId);
    }
}
