<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_organization(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/organizations', [
                'name' => 'Test Organization',
                'description' => 'Test Description',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Organization created successfully',
            ]);

        $this->assertDatabaseHas('organizations', [
            'name' => 'Test Organization',
        ]);
    }

    public function test_user_can_list_organizations(): void
    {
        $user = User::factory()->create();
        Organization::factory(3)->create(['user_id' => $user->id]);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/organizations');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_get_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/organizations/{$organization->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'description'],
            ]);
    }

    public function test_user_can_update_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->patchJson("/api/organizations/{$organization->id}", [
                'name' => 'Updated Organization',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Organization updated successfully']);

        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'name' => 'Updated Organization',
        ]);
    }

    public function test_user_can_delete_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/organizations/{$organization->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Organization deleted successfully']);

        $this->assertDatabaseMissing('organizations', [
            'id' => $organization->id,
        ]);
    }
}
