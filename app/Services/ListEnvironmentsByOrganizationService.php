<?php

namespace App\Services;

use App\Repositories\EnvironmentRepository;
use Illuminate\Database\Eloquent\Collection;

class ListEnvironmentsByOrganizationService
{
    public function __construct(private EnvironmentRepository $environments) {}

    public function handle(int $organizationId): Collection
    {
        return $this->environments->listByOrganization($organizationId);
    }
}
