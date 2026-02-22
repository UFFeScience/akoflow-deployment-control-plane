<?php

namespace App\Services;

use App\Repositories\ProviderRepository;
use App\Models\Provider;

class UpdateProviderHealthService
{
    public function __construct(private ProviderRepository $providers)
    {
    }

    public function handle(string $id, array $data): ?Provider
    {
        return $this->providers->update($id, $data);
    }
}
