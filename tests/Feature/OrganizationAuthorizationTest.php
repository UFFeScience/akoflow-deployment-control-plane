<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // ─── 404 for non-existent resources ───────────────────────────────────────

    public function test_get_nonexistent_organization_returns_404(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/organizations/99999');

        $response->assertStatus(404)
            ->assertJson(['error' => 'Organization not found']);
    }

    public function test_update_nonexistent_organization_returns_404(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->patchJson('/api/organizations/99999', ['name' => 'New Name']);

        $response->assertStatus(404);
    }

    public function test_delete_nonexistent_organization_returns_404(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson('/api/organizations/99999');

        $response->assertStatus(404);
    }

    // ─── 403 for cross-user access ─────────────────────────────────────────────

    public function test_user_cannot_get_another_users_organization(): void
    {
        $owner     = User::factory()->create();
        $otherUser = User::factory()->create();

        $organization = Organization::factory()->create(['user_id' => $owner->id]);

        $token = $otherUser->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/organizations/{$organization->id}");

        $response->assertStatus(403)
            ->assertJson(['error' => 'Unauthorized access to organization']);
    }

    public function test_user_cannot_update_another_users_organization(): void
    {
        $owner     = User::factory()->create();
        $otherUser = User::factory()->create();

        $organization = Organization::factory()->create(['user_id' => $owner->id]);

        $token = $otherUser->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->patchJson("/api/organizations/{$organization->id}", ['name' => 'Hacked']);

        $response->assertStatus(403);
    }

    public function test_user_cannot_delete_another_users_organization(): void
    {
        $owner     = User::factory()->create();
        $otherUser = User::factory()->create();

        $organization = Organization::factory()->create(['user_id' => $owner->id]);

        $token = $otherUser->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/organizations/{$organization->id}");

        $response->assertStatus(403);
    }

    public function test_user_cannot_list_members_of_another_users_organization(): void
    {
        $owner     = User::factory()->create();
        $otherUser = User::factory()->create();

        $organization = Organization::factory()->create(['user_id' => $owner->id]);

        $token = $otherUser->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/organizations/{$organization->id}/members");

        $response->assertStatus(403);
    }

    public function test_user_cannot_add_member_to_another_users_organization(): void
    {
        $owner       = User::factory()->create();
        $otherUser   = User::factory()->create();
        $newMember   = User::factory()->create();

        $organization = Organization::factory()->create(['user_id' => $owner->id]);

        $token = $otherUser->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/organizations/{$organization->id}/members", [
                'user_id' => $newMember->id,
                'role'    => 'member',
            ]);

        $response->assertStatus(403);
    }

    // ─── Members can access their own organizations ────────────────────────────

    public function test_member_can_get_organization_they_belong_to(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();

        $organization = Organization::factory()->create(['user_id' => $owner->id]);

        // Attach member via pivot
        $organization->members()->attach($member->id, ['role' => 'member']);

        $token = $member->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/organizations/{$organization->id}");

        $response->assertStatus(200);
    }

    // ─── List only scoped to user ──────────────────────────────────────────────

    public function test_list_organizations_returns_only_users_own_organizations(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Organization::factory(2)->create(['user_id' => $userA->id]);
        Organization::factory(3)->create(['user_id' => $userB->id]);

        $token = $userA->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/organizations');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
