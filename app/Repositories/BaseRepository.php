<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function find(string $id): ?Model
    {
        return $this->model->find($id);
    }

    public function all(array $with = []): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->with($with)->get();
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): ?Model
    {
        $m = $this->find($id);
        if (!$m) return null;
        $m->fill($data);
        $m->save();
        return $m;
    }

    public function delete(string $id): bool
    {
        $m = $this->find($id);
        if (!$m) return false;
        return (bool) $m->delete();
    }
}
