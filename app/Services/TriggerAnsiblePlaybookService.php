<?php

namespace App\Services;

use App\Enums\Messages;
use App\Messaging\Contracts\MessageDispatcherInterface;
use App\Models\AnsiblePlaybook;
use App\Models\AnsiblePlaybookRun;
use App\Models\Deployment;

class TriggerAnsiblePlaybookService
{
    public function __construct(
        private MessageDispatcherInterface $dispatcher,
    ) {}

    public function handle(int $playbookId, string $environmentId, ?int $deploymentId, string $triggeredBy): array
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

        $activity = AnsiblePlaybook::findOrFail($playbookId);

        $run = AnsiblePlaybookRun::create([
            'deployment_id' => $deploymentId,
            'playbook_id'   => $playbookId,
            'playbook_name' => $activity->name,
            'trigger'       => AnsiblePlaybook::TRIGGER_MANUAL,
            'status'        => AnsiblePlaybookRun::STATUS_QUEUED,
            'triggered_by'  => $triggeredBy,
        ]);

        $this->dispatcher->dispatch(Messages::EXECUTE_ANSIBLE_PLAYBOOK, [
            'playbook_id'   => $playbookId,
            'deployment_id' => $deploymentId,
            'run_id'        => $run->id,
            'triggered_by'  => $triggeredBy,
        ]);

        return [
            'deployment_found' => true,
            'run'              => $run,
        ];
    }
}
