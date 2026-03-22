<?php

namespace App\Services;

use App\Enums\Messages;
use App\Exceptions\DeploymentNotFoundException;
use App\Messaging\Contracts\MessageDispatcherInterface;
use App\Models\Deployment;
use App\Repositories\DeploymentRepository;

class DestroyDeploymentService
{
    public function __construct(
        private DeploymentRepository       $deployments,
        private MessageDispatcherInterface $dispatcher,
    ) {}

    public function handle(string $deploymentId): Deployment
    {
        /** @var Deployment|null $deployment */
        $deployment = $this->deployments->find($deploymentId);

        if (!$deployment) {
            throw new DeploymentNotFoundException();
        }

        $deployment->update(['status' => Deployment::STATUS_DESTROYING]);

        $this->dispatcher->dispatch(Messages::DESTROY_DEPLOYMENT, [
            'environment_id' => $deployment->environment_id,
            'deployment_id'  => $deployment->id,
        ]);

        return $deployment->fresh();
    }
}
