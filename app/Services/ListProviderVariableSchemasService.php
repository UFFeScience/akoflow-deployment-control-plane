<?php

namespace App\Services;

use App\Repositories\ProviderVariableSchemaRepository;
use Illuminate\Database\Eloquent\Collection;

class ListProviderVariableSchemasService
{
    public function __construct(private ProviderVariableSchemaRepository $schemas)
    {
    }

    public function handle(string $providerSlug): Collection
    {
        return $this->schemas->allByProviderSlug($providerSlug);
    }

    public function handleAll(): Collection
    {
        return $this->schemas->allOrdered();
    }
}
