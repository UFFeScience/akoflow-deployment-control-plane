<?php

namespace App\Services;

use App\Enums\Messages;
use App\Messaging\Contracts\MessageDispatcherInterface;
use App\Models\Deployment;

class TriggerAnsibleRunService
{
    public function __construct(private MessageDispatcherInterface $dispatcher) {}

    public function handle(string $environmentId, ?int $deploymentId): array
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

        $this->dispatcher->dispatch(Messages::CONFIGURE_ENVIRONMENT, [
            'deployment_id' => $deploymentId,
        ]);

        return ['deployment_found' => true, 'deployment_id' => $deploymentId];
    }
}
