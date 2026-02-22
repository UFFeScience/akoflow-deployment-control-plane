<?php

namespace App\Repositories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

class ProjectRepository
{
    public function findById(int $id): ?Project
    {
        return Project::find($id);
    }

    public function getByOrganizationId(int $organizationId): Collection
    {
        return Project::where('organization_id', $organizationId)
            ->with('organization')
            ->get();
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);
        return $project;
    }

    public function delete(Project $project): bool
    {
        return $project->delete();
    }
}
