<?php

namespace App\Jobs;

use App\Services\CheckProviderHealthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckProviderHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maximum seconds allowed for the cloud connectivity check */
    public int $timeout = 60;

    /** No retries — a failed health check is itself a valid result */
    public int $tries = 1;

    public function __construct(
        public readonly string $payload,
    ) {}

    public function handle(CheckProviderHealthService $service): void
    {
        $data = json_decode($this->payload, true);
        $service->handle((string) $data['provider_id']);
    }
}
