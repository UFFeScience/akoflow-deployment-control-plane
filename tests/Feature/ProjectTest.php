<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_project(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/organizations/{$organization->id}/projects", [
                'name' => 'Test Project',
                'description' => 'Test Description',
            ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Project created successfully']);

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
        ]);
    }

    public function test_user_can_list_projects(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['user_id' => $user->id]);
        Project::factory(3)->create(['organization_id' => $organization->id]);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/organizations/{$organization->id}/projects");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_get_project(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/organizations/{$organization->id}/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name', 'description']]);
    }

    public function test_user_can_update_project(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->patchJson("/api/organizations/{$organization->id}/projects/{$project->id}", [
                'name' => 'Updated Project',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Project updated successfully']);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project',
        ]);
    }

    public function test_user_can_delete_project(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/organizations/{$organization->id}/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Project deleted successfully']);

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }
}
