<?php

namespace Tests\Unit;

use App\Exceptions\ProjectNotFoundException;
use App\Exceptions\UnauthorizedOrganizationAccessException;
use App\Exceptions\UnauthorizedProjectAccessException;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAuthorizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(): ProjectAuthorizationService
    {
        return app(ProjectAuthorizationService::class);
    }

    public function test_owner_can_access_their_project(): void
    {
        $user         = User::factory()->create();
        $org          = Organization::factory()->create(['user_id' => $user->id]);
        $project      = Project::factory()->create(['organization_id' => $org->id]);
        $service      = $this->makeService();

        $result = $service->assertUserCanAccessProject($user, $org->id, $project->id);

        $this->assertEquals($project->id, $result->id);
    }

    public function test_throws_not_found_for_nonexistent_project(): void
    {
        $user    = User::factory()->create();
        $org     = Organization::factory()->create(['user_id' => $user->id]);
        $service = $this->makeService();

        $this->expectException(ProjectNotFoundException::class);

        $service->assertUserCanAccessProject($user, $org->id, 99999);
    }

    public function test_throws_unauthorized_when_project_belongs_to_different_organization(): void
    {
        $user   = User::factory()->create();
        $orgA   = Organization::factory()->create(['user_id' => $user->id]);
        $orgB   = Organization::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['organization_id' => $orgB->id]);
        $service = $this->makeService();

        $this->expectException(UnauthorizedProjectAccessException::class);

        // Project belongs to orgB but we pass orgA
        $service->assertUserCanAccessProject($user, $orgA->id, $project->id);
    }

    public function test_throws_unauthorized_when_user_does_not_belong_to_organization(): void
    {
        $owner    = User::factory()->create();
        $intruder = User::factory()->create();
        $org      = Organization::factory()->create(['user_id' => $owner->id]);
        $project  = Project::factory()->create(['organization_id' => $org->id]);
        $service  = $this->makeService();

        $this->expectException(UnauthorizedOrganizationAccessException::class);

        $service->assertUserCanAccessProject($intruder, $org->id, $project->id);
    }

    public function test_assert_by_project_id_without_org_also_checks_membership(): void
    {
        $owner    = User::factory()->create();
        $intruder = User::factory()->create();
        $org      = Organization::factory()->create(['user_id' => $owner->id]);
        $project  = Project::factory()->create(['organization_id' => $org->id]);
        $service  = $this->makeService();

        $this->expectException(UnauthorizedOrganizationAccessException::class);

        $service->assertUserCanAccessProjectById($intruder, $project->id);
    }
}
