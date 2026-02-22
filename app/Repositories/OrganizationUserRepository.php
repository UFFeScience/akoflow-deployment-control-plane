<?php

namespace App\Repositories;

use App\Models\OrganizationUser;
use Illuminate\Database\Eloquent\Collection;

class OrganizationUserRepository
{
    public function findByUserAndOrganization(int $userId, int $organizationId): ?OrganizationUser
    {
        return OrganizationUser::where('user_id', $userId)
            ->where('organization_id', $organizationId)
            ->first();
    }

    public function getByOrganizationId(int $organizationId): Collection
    {
        return OrganizationUser::where('organization_id', $organizationId)
            ->with('user')
            ->get();
    }

    public function create(array $data): OrganizationUser
    {
        return OrganizationUser::create($data);
    }

    public function update(OrganizationUser $member, array $data): OrganizationUser
    {
        $member->update($data);
        return $member;
    }

    public function delete(OrganizationUser $member): bool
    {
        return $member->delete();
    }
}
