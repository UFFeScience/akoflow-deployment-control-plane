<?php

namespace App\Jobs;

use App\Services\DestroyEnvironmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DestroyEnvironmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries   = 1;

    public function __construct(
        public readonly int $environmentId,
    ) {}

    public function handle(DestroyEnvironmentService $service): void
    {
        $service->handle($this->environmentId);
    }
}
