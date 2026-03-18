<?php

namespace Tests\Feature;

use App\Models\Environment;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnvironmentsByOrganizationTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $token = $user->createToken('api-token')->plainTextToken;
        return ['Authorization' => "Bearer $token"];
    }

    private function orgWithProjects(User $owner): array
    {
        $org      = Organization::factory()->create(['user_id' => $owner->id]);
        $projectA = Project::factory()->create(['organization_id' => $org->id]);
        $projectB = Project::factory()->create(['organization_id' => $org->id]);
        return [$org, $projectA, $projectB];
    }

    public function test_owner_can_list_all_environments_across_all_projects(): void
    {
        $user = User::factory()->create();
        [$org, $projectA, $projectB] = $this->orgWithProjects($user);

        Environment::create(['project_id' => $projectA->id, 'name' => 'Env A1', 'status' => 'PENDING']);
        Environment::create(['project_id' => $projectA->id, 'name' => 'Env A2', 'status' => 'RUNNING']);
        Environment::create(['project_id' => $projectB->id, 'name' => 'Env B1', 'status' => 'STOPPED']);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/organizations/{$org->id}/environments");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_response_includes_project_name(): void
    {
        $user = User::factory()->create();
        $org  = Organization::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['organization_id' => $org->id, 'name' => 'My Project']);

        Environment::create(['project_id' => $project->id, 'name' => 'Env X', 'status' => 'PENDING']);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/organizations/{$org->id}/environments");

        $response->assertStatus(200)
            ->assertJsonPath('data.0.project_name', 'My Project');
    }

    public function test_member_can_list_environments_of_their_organization(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $org    = Organization::factory()->create(['user_id' => $owner->id]);

        OrganizationUser::create([
            'organization_id' => $org->id,
            'user_id'         => $member->id,
            'role'            => 'member',
        ]);

        $project = Project::factory()->create(['organization_id' => $org->id]);
        Environment::create(['project_id' => $project->id, 'name' => 'Env 1', 'status' => 'PENDING']);

        $response = $this->withHeaders($this->authHeader($member))
            ->getJson("/api/organizations/{$org->id}/environments");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_cannot_list_environments_of_another_organization(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $orgB = Organization::factory()->create(['user_id' => $userB->id]);

        $response = $this->withHeaders($this->authHeader($userA))
            ->getJson("/api/organizations/{$orgB->id}/environments");

        $response->assertStatus(403);
    }

    public function test_returns_empty_when_no_environments_exist(): void
    {
        $user = User::factory()->create();
        $org  = Organization::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/organizations/{$org->id}/environments");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_only_returns_environments_from_users_organization(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $orgA    = Organization::factory()->create(['user_id' => $userA->id]);
        $projectA = Project::factory()->create(['organization_id' => $orgA->id]);
        Environment::create(['project_id' => $projectA->id, 'name' => 'Env A', 'status' => 'PENDING']);

        $orgB    = Organization::factory()->create(['user_id' => $userB->id]);
        $projectB = Project::factory()->create(['organization_id' => $orgB->id]);
        Environment::create(['project_id' => $projectB->id, 'name' => 'Env B', 'status' => 'PENDING']);

        $response = $this->withHeaders($this->authHeader($userA))
            ->getJson("/api/organizations/{$orgA->id}/environments");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Env A');
    }
}
