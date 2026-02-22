<?php

namespace App\Services;

use App\Repositories\ProviderRepository;
use App\Models\Provider;

class ProviderService
{
    public function __construct(protected ProviderRepository $repo)
    {
    }

    public function list(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repo->all();
    }

    public function create(array $data): Provider
    {
        return $this->repo->create($data);
    }

    public function updateHealth(string $id, array $data): ?Provider
    {
        return $this->repo->updateHealth($id, $data);
    }
}
