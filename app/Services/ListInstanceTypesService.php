<?php

namespace App\Services;

use App\Repositories\InstanceTypeRepository;
use Illuminate\Support\Collection;

class ListInstanceTypesService
{
    public function __construct(private InstanceTypeRepository $types)
    {
    }

    public function handle(): Collection
    {
        return $this->types->all(['provider']);
    }
}
