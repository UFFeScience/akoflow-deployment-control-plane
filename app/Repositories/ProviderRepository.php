<?php

namespace App\Repositories;

use App\Models\Provider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProviderRepository extends BaseRepository
{
    public function __construct(Provider $model)
    {
        parent::__construct($model);
    }

    public function allWithCredentialsCount(): Collection
    {
        return $this->model->withCount('credentials')->orderBy('name')->get();
    }

    public function allCloudWithCredentialsCount(): Collection
    {
        return $this->model
            ->where('type', 'CLOUD')
            ->withCount('credentials')
            ->orderBy('name')
            ->get();
    }

    public function findOrFailById(string $id): Provider
    {
        $provider = $this->find($id);

        if (!$provider) {
            throw (new ModelNotFoundException())->setModel(Provider::class, $id);
        }

        /** @var Provider $provider */
        return $provider;
    }

    public function findWithCredentialsCount(string $id): ?Provider
    {
        return $this->model->withCount('credentials')->find($id);
    }

    public function findWithCredentialsCountOrFail(string $id): Provider
    {
        $provider = $this->findWithCredentialsCount($id);

        if (!$provider) {
            throw (new ModelNotFoundException())->setModel(Provider::class, $id);
        }

        return $provider;
    }

    public function updateHealth(string $id, array $data): ?Provider
    {
        return $this->update($id, $data);
    }
}
