<?php

namespace App\Repositories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

class OrganizationRepository
{
    public function findById(int $id): ?Organization
    {
        return Organization::find($id);
    }

    public function findByIdWithMembers(int $id): ?Organization
    {
        return Organization::with('members')->find($id);
    }

    public function findByIdWithProjects(int $id): ?Organization
    {
        return Organization::with('projects')->find($id);
    }

    public function findByIdWithAll(int $id): ?Organization
    {
        return Organization::with(['members', 'projects'])->find($id);
    }

    public function getByUserId(int $userId): Collection
    {
        return Organization::where('user_id', $userId)
            ->orWhereHas('members', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with('members')
            ->get();
    }

    public function create(array $data): Organization
    {
        return Organization::create($data);
    }

    public function update(Organization $organization, array $data): Organization
    {
        $organization->update($data);
        return $organization;
    }

    public function delete(Organization $organization): bool
    {
        return $organization->delete();
    }
}
