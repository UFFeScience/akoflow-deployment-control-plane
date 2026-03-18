<?php

namespace App\Services;

use App\Repositories\EnvironmentTemplateRepository;
use App\Models\EnvironmentTemplate;

class CreateEnvironmentTemplateService
{
    public function __construct(private EnvironmentTemplateRepository $templates)
    {
    }

    public function handle(array $data): EnvironmentTemplate
    {
        return $this->templates->create($data);
    }
}
