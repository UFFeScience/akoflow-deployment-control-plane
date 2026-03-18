<?php

namespace App\Services;

use App\Models\Provider;
use App\Repositories\ProviderRepository;

class ShowProviderService
{
    public function __construct(private ProviderRepository $providers)
    {
    }

    public function handle(string $id, string $organizationId): Provider
    {
        return $this->providers->findByOrganizationWithCredentialsCountOrFail($id, $organizationId);
    }
}
