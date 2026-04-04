<?php

namespace App\Services;

use App\Enums\HealthStatus;
use App\Models\ProviderCredential;
use App\Repositories\ProviderCredentialHealthLogRepository;
use App\Repositories\ProviderCredentialRepository;

class CheckCredentialHealthService
{
    public function __construct(
        private ProviderCredentialRepository          $credentialRepository,
        private ProviderCredentialHealthLogRepository $logRepository,
        private ProviderCredentialResolverService     $credentialResolver,
        private TerraformHealthCheckRunnerService     $runner,
    ) {}

    public function handle(ProviderCredential $credential): ProviderCredential
    {
        $credential->loadMissing('values');

        $template = trim((string) ($credential->health_check_template ?? ''));

        if ($template === '') {
            return $this->persist(
                $credential,
                false,
                'No health check template configured. Add a Terraform HCL template to this credential.',
            );
        }

        $env = $this->credentialResolver->resolveForCredential($credential);

        $result = $this->runner->run((string) $credential->id, [
            'main_tf' => $template,
            'env'     => $env,
            'tfvars'  => [],
        ]);

        return $this->persist($credential, $result['healthy'], $result['message']);
    }

    private function persist(ProviderCredential $credential, bool $healthy, string $message): ProviderCredential
    {
        $status = $healthy ? HealthStatus::HEALTHY->value : HealthStatus::UNHEALTHY->value;

        $updated = $this->credentialRepository->updateHealth(
            (string) $credential->id,
            $status,
            $message,
        );

        $this->logRepository->createForCredential(
            (string) $credential->id,
            $status,
            $message,
        );

        return $updated;
    }
}
