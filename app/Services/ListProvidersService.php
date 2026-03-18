<?php

namespace App\Services;

use App\Repositories\ProviderRepository;
use Illuminate\Database\Eloquent\Collection;

class ListProvidersService
{
    public function __construct(private ProviderRepository $providers)
    {
    }

    public function handle(string $organizationId): Collection
    {
        return $this->providers->allByOrganizationWithCredentialsCount($organizationId);
    }

    public function onlyCloud(string $organizationId): Collection
    {
        return $this->providers->allCloudByOrganizationWithCredentialsCount($organizationId);
    }
}
