<?php

namespace App\Repositories;

use App\Models\DeploymentTemplate;

class DeploymentTemplateRepository extends BaseRepository
{
    public function __construct(DeploymentTemplate $model)
    {
        parent::__construct($model);
    }
}
