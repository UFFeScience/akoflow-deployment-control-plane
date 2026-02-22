<?php

namespace App\Repositories;

use App\Models\Provider;

class ProviderRepository extends BaseRepository
{
    public function __construct(Provider $model)
    {
        parent::__construct($model);
    }

    public function updateHealth(string $id, array $data): ?Provider
    {
        return $this->update($id, $data);
    }
}
