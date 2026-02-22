<?php

namespace App\Repositories;

use App\Models\ExperimentTemplate;

class ExperimentTemplateRepository extends BaseRepository
{
    public function __construct(ExperimentTemplate $model)
    {
        parent::__construct($model);
    }
}
