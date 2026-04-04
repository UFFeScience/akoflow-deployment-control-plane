<?php

namespace App\Services;

use App\Enums\Messages;
use App\Messaging\Contracts\MessageDispatcherInterface;
use App\Models\Deployment;
use App\Models\EnvironmentTemplateRunbook;
use App\Models\RunbookRun;
use App\Repositories\RunbookRunRepository;

class TriggerRunbookRunService
{
    public function __construct(
        private MessageDispatcherInterface $dispatcher,
        private RunbookRunRepository       $runRepository,
    ) {}

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

        // Create the run record immediately so the UI can show it as QUEUED
        $run = $this->runRepository->create([
            'deployment_id' => $deploymentId,
            'runbook_id'    => $runbookId,
            'runbook_name'  => $runbook->name,
            'status'        => RunbookRun::STATUS_QUEUED,
            'triggered_by'  => $triggeredBy,
        ]);

        $this->dispatcher->dispatch(Messages::EXECUTE_RUNBOOK, [
            'runbook_id'    => $runbookId,
            'deployment_id' => $deploymentId,
            'run_id'        => $run->id,
            'triggered_by'  => $triggeredBy,
        ]);

        return [
            'deployment_found' => true,
            'run'              => $run,
            'runbook_name'     => $runbook->name,
            'deployment_id'    => $deploymentId,
        ];
    }
}
