<?php

namespace App\Services;

use App\Enums\Messages;
use App\Messaging\Contracts\MessageDispatcherInterface;
use App\Models\Deployment;
use App\Models\Environment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateEnvironmentWithClusterService
{
    public function __construct(
        private CreateEnvironmentService    $createEnvironment,
        private CreateClusterService        $createCluster,
        private MessageDispatcherInterface  $dispatcher,
    ) {}

    /**
     * Creates an environment and, when deployment data is provided, a deployment —
     * all within a single database transaction so both entities are atomic.
     *
     * @param  string  $projectId
     * @param  array   $data  Validated data from ProvisionEnvironmentRequest
     * @return array{environment: Environment, deployment: Deployment|null}
     *
     * @throws InvalidArgumentException
     */
    public function handle(string $projectId, array $data): array
    {
        if (empty($data['deployment']) || empty($data['deployment']['provider_id'])) {
            throw new InvalidArgumentException('Deployment data with provider_id is required.');
        }

        return DB::transaction(function () use ($projectId, $data) {
            $clusterData = $data['deployment'];
            unset($data['deployment']);

            $environment = $this->createEnvironment->handle($projectId, $data);

            $deployment = $this->createCluster->handle((string) $environment->id, $clusterData);

            $this->dispatcher->dispatch(Messages::PROVISION_ENVIRONMENT, [
                'environment_id' => $environment->id,
            ]);

            return [
                'environment' => $environment,
                'deployment'     => $deployment,
            ];
        });
    }

}

