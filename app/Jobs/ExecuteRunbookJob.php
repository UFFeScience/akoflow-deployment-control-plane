<?php

namespace App\Jobs;

use App\Services\ExecuteRunbookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteRunbookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries   = 1;

    public function __construct(
        public readonly string $payload,
    ) {}

    public function handle(ExecuteRunbookService $service): void
    {
        $data = json_decode($this->payload, true);
        $service->handle(
            (int) $data['runbook_id'],
            (int) $data['deployment_id'],
            (string) ($data['triggered_by'] ?? 'system'),
        );
    }
}
