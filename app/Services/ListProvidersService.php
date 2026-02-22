<?php

namespace App\Services;

use App\Repositories\ProviderRepository;
use Illuminate\Support\Collection;

class ListProvidersService
{
    public function __construct(private ProviderRepository $providers)
    {
    }

    public function handle(): Collection
    {
        return $this->providers->all();
    }
}
