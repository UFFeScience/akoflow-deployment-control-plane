<?php

namespace App\Services;

use App\Enums\Messages;
use App\Messaging\Contracts\MessageDispatcherInterface;
use App\Models\Deployment;
use App\Models\EnvironmentTemplateRunbook;

class TriggerRunbookRunService
{
    public function __construct(private MessageDispatcherInterface $dispatcher) {}

    public function handle(int $runbookId, string $environmentId, ?int $deploymentId, string $triggeredBy): array
    {
        if (!$deploymentId) {
            $deployment   = Deployment::where('environment_id', $environmentId)
                ->orderByDesc('created_at')
                ->first();
            $deploymentId = $deployment?->id;
        }

        if (!$deploymentId) {
            return ['deployment_found' => false];
        }

        $runbook = EnvironmentTemplateRunbook::findOrFail($runbookId);

        $this->dispatcher->dispatch(Messages::EXECUTE_RUNBOOK, [
            'runbook_id'    => $runbookId,
            'deployment_id' => $deploymentId,
            'triggered_by'  => $triggeredBy,
        ]);

        return [
            'deployment_found' => true,
            'runbook_name'     => $runbook->name,
            'deployment_id'    => $deploymentId,
        ];
    }
}
