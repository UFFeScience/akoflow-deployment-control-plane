<?php

namespace App\Repositories;

use App\Models\ClusterTemplate;

class ClusterTemplateRepository extends BaseRepository
{
    public function __construct(ClusterTemplate $model)
    {
        parent::__construct($model);
    }
}
