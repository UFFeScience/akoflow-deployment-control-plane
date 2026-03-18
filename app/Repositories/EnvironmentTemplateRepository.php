<?php

namespace App\Repositories;

use App\Models\EnvironmentTemplate;

class EnvironmentTemplateRepository extends BaseRepository
{
    public function __construct(EnvironmentTemplate $model)
    {
        parent::__construct($model);
    }
}
