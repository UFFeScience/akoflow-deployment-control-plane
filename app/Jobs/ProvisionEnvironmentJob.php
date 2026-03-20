<?php

namespace App\Jobs;

use App\Services\ProvisionEnvironmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProvisionEnvironmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maximum wall-clock seconds allowed for the Terraform run */
    public int $timeout = 3600;

    /** Retries on unexpected failure */
    public int $tries = 1;

    public function __construct(
        public readonly string $payload,
    ) {}

    public function handle(ProvisionEnvironmentService $service): void
    {
        $data = json_decode($this->payload, true);
        $service->handle((int) $data['environment_id']);
    }
}
