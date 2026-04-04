<?php

namespace App\Repositories;

use App\Models\EnvironmentTemplateRunbook;
use Illuminate\Database\Eloquent\Collection;

class RunbookRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new EnvironmentTemplateRunbook());
    }

    public function findByProviderConfig(string $configId): Collection
    {
        return EnvironmentTemplateRunbook::where('provider_configuration_id', $configId)
            ->orderBy('position')
            ->get();
    }
}
