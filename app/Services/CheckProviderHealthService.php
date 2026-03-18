<?php

namespace App\Services;

use App\Enums\HealthStatus;
use App\Models\Provider;
use App\Repositories\ProviderRepository;
use App\Services\CloudHealthChecks\AwsHealthCheckService;
use App\Services\CloudHealthChecks\GcpHealthCheckService;
use App\Services\CloudHealthChecks\SlurmHealthCheckService;

class CheckProviderHealthService
{
    public function __construct(
        private ProviderRepository               $providerRepository,
        private AwsHealthCheckService            $awsHealthCheck,
        private GcpHealthCheckService            $gcpHealthCheck,
        private SlurmHealthCheckService          $slurmHealthCheck,
        private TerraformHealthCheckRunnerService $runner,
    ) {}

    public function handle(string $providerId, string $organizationId): Provider
    {
        $provider = $this->providerRepository->findByOrganizationOrFail($providerId, $organizationId);

        $activeCredential = $provider->credentials()
            ->where('is_active', true)
            ->with('values')
            ->first();

        if (!$activeCredential) {
            return $this->persistHealth($provider, false, 'No active credential configured for this provider.');
        }

        /** @var array<string, string> $credentialValues */
        $credentialValues = $activeCredential->values->pluck('field_value', 'field_key')->toArray();

        try {
            $workspace = match ($provider->slug) {
                'aws'   => $this->awsHealthCheck->buildWorkspace($credentialValues),
                'gcp'   => $this->gcpHealthCheck->buildWorkspace($credentialValues),
                'slurm' => $this->slurmHealthCheck->buildWorkspace($credentialValues),
                default => null,
            };
        } catch (\InvalidArgumentException $e) {
            return $this->persistHealth($provider, false, $e->getMessage());
        }

        if ($workspace === null) {
            return $this->persistHealth(
                $provider,
                false,
                "Health check via Terraform is not supported for provider slug '{$provider->slug}'.",
            );
        }

        $result = $this->runner->run($providerId, $workspace);

        return $this->persistHealth($provider, $result['healthy'], $result['message']);
    }

    private function persistHealth(Provider $provider, bool $healthy, string $message): Provider
    {
        $provider->update([
            'health_status'        => $healthy ? HealthStatus::HEALTHY->value : HealthStatus::UNHEALTHY->value,
            'health_message'       => $message,
            'last_health_check_at' => now(),
        ]);

        return $provider->fresh();
    }
}

