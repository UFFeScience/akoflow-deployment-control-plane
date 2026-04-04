<?php

namespace App\Services;

use App\Models\ProviderCredential;
use App\Repositories\ProviderRepository;

/**
 * Triggers a health check for every credential that belongs to a provider.
 * Delegates the actual cloud check to CheckCredentialHealthService.
 */
class CheckProviderHealthService
{
    public function __construct(
        private ProviderRepository           $providerRepository,
        private CheckCredentialHealthService $checkCredentialHealth,
    ) {}

    /**
     * @return ProviderCredential[]  Updated credentials.
     */
    public function handle(string $providerId, string $organizationId): array
    {
        $provider = $this->providerRepository->findByOrganizationOrFail($providerId, $organizationId);

        $credentials = $provider->credentials()->with('values')->get();

        if ($credentials->isEmpty()) {
            return [];
        }

        $results = [];
        foreach ($credentials as $credential) {
            /** @var ProviderCredential $credential */
            $credential->setRelation('provider', $provider);
            $results[] = $this->checkCredentialHealth->handle($credential);
        }

        return $results;
    }
}

