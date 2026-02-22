<?php

namespace App\Services;

use App\Repositories\ExperimentTemplateRepository;
use Illuminate\Support\Collection;

class ListExperimentTemplatesService
{
    public function __construct(private ExperimentTemplateRepository $templates)
    {
    }

    public function handle(): Collection
    {
        return $this->templates->all(['versions']);
    }
}
