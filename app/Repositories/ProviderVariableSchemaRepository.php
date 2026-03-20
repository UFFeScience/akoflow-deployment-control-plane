<?php

namespace App\Repositories;

use App\Models\ProviderVariableSchema;
use Illuminate\Database\Eloquent\Collection;

class ProviderVariableSchemaRepository extends BaseRepository
{
    public function __construct(ProviderVariableSchema $model)
    {
        parent::__construct($model);
    }

    public function allByProviderSlug(string $providerSlug): Collection
    {
        return $this->model
            ->where('provider_slug', $providerSlug)
            ->orderBy('section')
            ->orderBy('position')
            ->get();
    }

    public function allOrdered(): Collection
    {
        return $this->model
            ->orderBy('provider_slug')
            ->orderBy('section')
            ->orderBy('position')
            ->get();
    }

    public function createForProvider(string $providerSlug, array $data): ProviderVariableSchema
    {
        /** @var ProviderVariableSchema $schema */
        $schema = $this->model->create(array_merge($data, ['provider_slug' => $providerSlug]));
        return $schema;
    }
}
