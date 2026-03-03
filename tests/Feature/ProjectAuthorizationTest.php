<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // ─── 404 for non-existent resources ───────────────────────────────────────

    public function test_get_nonexistent_project_returns_404(): void
    {
        $user         = User::factory()->create();
        $organization = Organization::factory()->create(['user_id' => $user->id]);
        $token        = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/organizations/{$organization->id}/projects/99999");

        $response->assertStatus(404)
            ->assertJson(['error' => 'Project not found']);
    }

    public function test_update_nonexistent_project_returns_404(): void
    {
        $user         = User::factory()->create();
        $organization = Organization::factory()->create(['user_id' => $user->id]);
        $token        = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->patchJson("/api/organizations/{$organization->id}/projects/99999", ['name' => 'Ghost']);

        $response->assertStatus(404);
    }

    public function test_delete_nonexistent_project_returns_404(): void
    {
        $user         = User::factory()->create();
        $organization = Organization::factory()->create(['user_id' => $user->id]);
        $token        = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/organizations/{$organization->id}/projects/99999");

        $response->assertStatus(404);
    }

    // ─── 403 for cross-organization project access ────────────────────────────

    public function test_user_cannot_get_project_from_another_organization(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $orgA    = Organization::factory()->create(['user_id' => $ownerA->id]);
        $orgB    = Organization::factory()->create(['user_id' => $ownerB->id]);
        $project = Project::factory()->create(['organization_id' => $orgB->id]);

        $token = $ownerA->createToken('api-token')->plainTextToken;

        // ownerA tries to access a project that belongs to orgB
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/organizations/{$orgA->id}/projects/{$project->id}");

        // Project exists but belongs to orgB, not orgA → 403
        $response->assertStatus(403);
    }

    public function test_user_cannot_update_project_from_another_organization(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $orgA    = Organization::factory()->create(['user_id' => $ownerA->id]);
        $orgB    = Organization::factory()->create(['user_id' => $ownerB->id]);
        $project = Project::factory()->create(['organization_id' => $orgB->id]);

        $token = $ownerA->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->patchJson("/api/organizations/{$orgA->id}/projects/{$project->id}", ['name' => 'Hacked']);

        $response->assertStatus(403);
    }

    public function test_user_cannot_delete_project_from_another_organization(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $orgA    = Organization::factory()->create(['user_id' => $ownerA->id]);
        $orgB    = Organization::factory()->create(['user_id' => $ownerB->id]);
        $project = Project::factory()->create(['organization_id' => $orgB->id]);

        $token = $ownerA->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/organizations/{$orgA->id}/projects/{$project->id}");

        $response->assertStatus(403);
    }

    public function test_user_cannot_list_projects_of_another_users_organization(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $orgB = Organization::factory()->create(['user_id' => $ownerB->id]);
        Project::factory(3)->create(['organization_id' => $orgB->id]);

        $token = $ownerA->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/organizations/{$orgB->id}/projects");

        $response->assertStatus(403);
    }

    public function test_user_cannot_create_project_in_another_users_organization(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $orgB  = Organization::factory()->create(['user_id' => $ownerB->id]);
        $token = $ownerA->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/organizations/{$orgB->id}/projects", [
                'name'        => 'Intruder Project',
                'description' => 'Should fail',
            ]);

        $response->assertStatus(403);
    }

    // ─── Organization member can access its projects ──────────────────────────

    public function test_member_can_list_projects_in_their_organization(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();

        $org = Organization::factory()->create(['user_id' => $owner->id]);
        $org->members()->attach($member->id, ['role' => 'member']);

        Project::factory(2)->create(['organization_id' => $org->id]);

        $token = $member->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/organizations/{$org->id}/projects");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    // ─── Unauthenticated access ───────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_access_projects(): void
    {
        $user         = User::factory()->create();
        $organization = Organization::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson("/api/organizations/{$organization->id}/projects");

        $response->assertStatus(401);
    }
}
