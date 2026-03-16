<?php

namespace App\Services;

use App\Repositories\ProviderRepository;
use Illuminate\Database\Eloquent\Collection;

class ListProvidersService
{
    public function __construct(private ProviderRepository $providers)
    {
    }

    public function handle(): Collection
    {
        return $this->providers->allWithCredentialsCount();
    }

    public function onlyCloud(): Collection
    {
        return $this->providers->allCloudWithCredentialsCount();
    }
}
