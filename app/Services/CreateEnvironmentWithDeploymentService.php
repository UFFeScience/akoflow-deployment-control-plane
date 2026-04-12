<?php

namespace App\Services;

use App\Enums\Messages;
use App\Messaging\Contracts\MessageDispatcherInterface;
use App\Models\AnsiblePlaybook;
use App\Models\AnsiblePlaybookRun;
use App\Models\Deployment;
use App\Models\Environment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateEnvironmentWithDeploymentService
{
    public function __construct(
        private CreateEnvironmentService                $createEnvironment,
        private CreateDeploymentService                 $createDeployment,
        private EnvironmentDeploymentProviderService    $providerResolver,
        private AnsiblePlaybookResolverService          $activityResolver,
        private AnsiblePlaybookTaskHostStatusService    $taskHostStatusService,
        private MessageDispatcherInterface              $dispatcher,
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
        if (empty($data['deployment']) || empty($data['deployment']['provider_credentials'])) {
            throw new InvalidArgumentException('Deployment data with provider_credentials is required.');
        }

        return DB::transaction(function () use ($projectId, $data) {
            $deploymentData = $data['deployment'];
            unset($data['deployment']);

            $environment = $this->createEnvironment->handle($projectId, $data);

            $deployment = $this->createDeployment->handle((string) $environment->id, $deploymentData);

            $this->prepareAfterProvisionActivities($environment, $deployment);

            $this->dispatcher->dispatch(Messages::PROVISION_ENVIRONMENT, [
                'environment_id' => $environment->id,
                'deployment_id'  => $deployment->id,
            ]);

            return [
                'environment' => $environment,
                'deployment'  => $deployment,
            ];
        });
    }

    private function prepareAfterProvisionActivities(Environment $environment, Deployment $deployment): void
    {
        $provider = $this->providerResolver->resolveFromDeployment($deployment);

        $playbooks = $this->activityResolver->resolve(
            (int) $environment->environment_template_version_id,
            $provider->type,
            AnsiblePlaybook::TRIGGER_AFTER_PROVISION,
        );

        foreach ($playbooks as $activity) {
            $run = AnsiblePlaybookRun::firstOrCreate([
                'deployment_id' => $deployment->id,
                'playbook_id'   => $activity->id,
                'trigger'       => AnsiblePlaybook::TRIGGER_AFTER_PROVISION,
            ], [
                'playbook_name' => $activity->name,
                'status'        => AnsiblePlaybookRun::STATUS_QUEUED,
                'provider_type' => $provider->type,
                'triggered_by'  => 'system',
            ]);

            $this->taskHostStatusService->initializePending($run->fresh());
        }
    }

}

