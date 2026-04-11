<?php

namespace App\Jobs;

use App\Services\ExecuteAnsiblePlaybookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteAnsiblePlaybookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries   = 1;

    public function __construct(
        public readonly string $payload,
    ) {}

    public function handle(ExecuteAnsiblePlaybookService $service): void
    {
        $data = json_decode($this->payload, true);

        $service->handle(
            (int) $data['playbook_id'],
            (int) $data['deployment_id'],
            (string) ($data['triggered_by'] ?? 'system'),
            isset($data['run_id']) ? (int) $data['run_id'] : null,
        );
    }
}
