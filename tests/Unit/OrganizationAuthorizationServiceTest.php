<?php

namespace Tests\Unit;

use App\Exceptions\OrganizationNotFoundException;
use App\Exceptions\UnauthorizedOrganizationAccessException;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationAuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationAuthorizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(): OrganizationAuthorizationService
    {
        return app(OrganizationAuthorizationService::class);
    }

    public function test_owner_can_access_their_organization(): void
    {
        $user         = User::factory()->create();
        $organization = Organization::factory()->create(['user_id' => $user->id]);
        $service      = $this->makeService();

        // Should not throw
        $service->assertUserBelongsToOrganization($user, $organization->id);

        $this->assertTrue(true);
    }

    public function test_member_can_access_organization_they_belong_to(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();

        $organization = Organization::factory()->create(['user_id' => $owner->id]);
        $organization->members()->attach($member->id, ['role' => 'member']);

        $service = $this->makeService();

        // Should not throw
        $service->assertUserBelongsToOrganization($member, $organization->id);

        $this->assertTrue(true);
    }

    public function test_unauthorized_user_cannot_access_organization(): void
    {
        $owner     = User::factory()->create();
        $intruder  = User::factory()->create();

        $organization = Organization::factory()->create(['user_id' => $owner->id]);
        $service      = $this->makeService();

        $this->expectException(UnauthorizedOrganizationAccessException::class);

        $service->assertUserBelongsToOrganization($intruder, $organization->id);
    }

    public function test_throws_not_found_for_nonexistent_organization(): void
    {
        $user    = User::factory()->create();
        $service = $this->makeService();

        $this->expectException(OrganizationNotFoundException::class);

        $service->assertUserBelongsToOrganization($user, 99999);
    }
}
