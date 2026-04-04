<?php

namespace App\Services;

use App\Enums\HealthStatus;
use App\Models\ProviderCredential;
use App\Repositories\ProviderCredentialHealthLogRepository;
use App\Repositories\ProviderCredentialRepository;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunProviderHealthCheckSweepService
{
    public function __construct(
        private ProviderCredentialRepository        $credentialRepository,
        private ProviderCredentialHealthLogRepository $logRepository,
        private CheckCredentialHealthService        $checkCredentialHealth,
    ) {}

    /**
     * Runs a health check against every credential across all organisations.
     *
     * @return array<int, array{credential: ProviderCredential, status: string, exception: string|null}>
     */
    public function execute(): array
    {
        $credentials = $this->credentialRepository->allWithProviderAndValues();
        $results     = [];

        foreach ($credentials as $credential) {
            /** @var ProviderCredential $credential */
            $results[] = $this->checkOne($credential);
        }

        return $results;
    }

    private function checkOne(ProviderCredential $credential): array
    {
        try {
            $updated = $this->checkCredentialHealth->handle($credential);

            return [
                'credential' => $updated,
                'status'     => $updated->health_status,
                'exception'  => null,
            ];
        } catch (Throwable $e) {
            Log::error('RunProviderHealthCheckSweepService: unhandled exception', [
                'credential_id' => $credential->id,
                'provider_slug' => $credential->provider?->slug,
                'error'         => $e->getMessage(),
            ]);

            $this->credentialRepository->updateHealth(
                (string) $credential->id,
                HealthStatus::UNHEALTHY->value,
                'Health check exception: ' . $e->getMessage(),
            );

            $this->logRepository->createForCredential(
                (string) $credential->id,
                HealthStatus::UNHEALTHY->value,
                'Health check exception: ' . $e->getMessage(),
            );

            return [
                'credential' => $credential,
                'status'     => HealthStatus::UNHEALTHY->value,
                'exception'  => $e->getMessage(),
            ];
        }
    }
}
