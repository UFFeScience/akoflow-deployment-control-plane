<?php

namespace App\Repositories;

use App\Models\Environment;

class EnvironmentRepository extends BaseRepository
{
    public function __construct(Environment $model)
    {
        parent::__construct($model);
    }

    public function listByProject(string $projectId)
    {
        return $this->model
            ->where('project_id', $projectId)
            ->with(['templateVersion.template'])
            ->get();
    }

    public function findByProject(string $projectId, string $id): ?Environment
    {
        return $this->model
            ->where('project_id', $projectId)
            ->where('id', $id)
            ->with(['templateVersion.template'])
            ->first();
    }

    public function listByOrganization(int $organizationId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model
            ->whereHas('project', fn($q) => $q->where('organization_id', $organizationId))
            ->with(['templateVersion.template', 'project'])
            ->get();
    }
}
