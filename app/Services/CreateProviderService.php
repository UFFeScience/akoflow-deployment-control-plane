<?php

namespace App\Services;

use App\Repositories\ProviderRepository;
use App\Models\Provider;

class CreateProviderService
{
    public function __construct(private ProviderRepository $providers)
    {
    }

    public function handle(array $data): Provider
    {
        return $this->providers->create($data);
    }
}
