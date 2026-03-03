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

    public function allBySlug(string $slug): Collection
    {
        return $this->model
            ->where('provider_slug', $slug)
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
}
