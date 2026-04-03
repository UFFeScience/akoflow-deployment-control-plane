<?php

namespace App\Jobs;

use App\Services\ConfigureEnvironmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConfigureEnvironmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries   = 1;

    public function __construct(
        public readonly string $payload,
    ) {}

    public function handle(ConfigureEnvironmentService $service): void
    {
        $data = json_decode($this->payload, true);
        $service->handle((int) $data['deployment_id']);
    }
}
