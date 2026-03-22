<?php

namespace App\Services;

use App\Models\ProvisionedResource;
use App\Repositories\ProvisionedResourceRepository;

class GetResourceService
{
    public function __construct(private ProvisionedResourceRepository $resources) {}

    public function handle(string $id): ?ProvisionedResource
    {
        return $this->resources->find($id);
    }
}
