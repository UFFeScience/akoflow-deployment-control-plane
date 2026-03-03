<?php

namespace App\Services;

use App\Repositories\ProviderCredentialRepository;
use App\Repositories\ProviderRepository;
use Illuminate\Database\Eloquent\Collection;

class ListProviderCredentialsService
{
    public function __construct(
        private ProviderRepository $providers,
        private ProviderCredentialRepository $credentials,
    ) {
    }

    public function handle(string $providerId): Collection
    {
        $this->providers->findOrFailById($providerId);

        return $this->credentials->allByProvider($providerId);
    }
}
