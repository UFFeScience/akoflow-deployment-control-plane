<?php

namespace App\Services;

use App\Repositories\EnvironmentTemplateRepository;
use Illuminate\Support\Collection;

class ListEnvironmentTemplatesService
{
    public function __construct(private EnvironmentTemplateRepository $templates)
    {
    }

    public function handle(): Collection
    {
        return $this->templates->all(['versions']);
    }
}
