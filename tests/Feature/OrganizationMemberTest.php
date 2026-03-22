<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationMemberTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $token = $user->createToken('api-token')->plainTextToken;

        return ['Authorization' => "Bearer $token"];
    }

    // ── List members ──────────────────────────────────────────────────────────

    public function test_owner_can_list_organization_members(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $org    = Organization::factory()->create(['user_id' => $owner->id]);

        $org->members()->attach($member->id, ['role' => 'member']);

        $response = $this->withHeaders($this->authHeader($owner))
            ->getJson("/api/organizations/{$org->id}/members");

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'data']);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    // ── Add member ────────────────────────────────────────────────────────────

    public function test_owner_can_add_member_to_organization(): void
    {
        $owner   = User::factory()->create();
        $newUser = User::factory()->create();
        $org     = Organization::factory()->create(['user_id' => $owner->id]);

        $response = $this->withHeaders($this->authHeader($owner))
            ->postJson("/api/organizations/{$org->id}/members", [
                'user_id' => $newUser->id,
                'role'    => 'member',
            ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Member added successfully'])
            ->assertJsonStructure(['data' => ['user_id', 'organization_id', 'role']]);

        $this->assertDatabaseHas('organization_users', [
            'organization_id' => $org->id,
            'user_id'         => $newUser->id,
            'role'            => 'member',
        ]);
    }

    public function test_adding_duplicate_member_returns_conflict(): void
    {
        $owner   = User::factory()->create();
        $member  = User::factory()->create();
        $org     = Organization::factory()->create(['user_id' => $owner->id]);

        $org->members()->attach($member->id, ['role' => 'member']);

        $response = $this->withHeaders($this->authHeader($owner))
            ->postJson("/api/organizations/{$org->id}/members", [
                'user_id' => $member->id,
                'role'    => 'member',
            ]);

        $response->assertStatus(409);
    }

    public function test_add_member_with_non_existent_user_returns_validation_error(): void
    {
        $owner = User::factory()->create();
        $org   = Organization::factory()->create(['user_id' => $owner->id]);

        $response = $this->withHeaders($this->authHeader($owner))
            ->postJson("/api/organizations/{$org->id}/members", [
                'user_id' => 99999,
                'role'    => 'member',
            ]);

        $response->assertStatus(422);
        $this->assertStringContainsString(
            'user id',
            strtolower(implode(' ', $response->json('errors') ?? []))
        );
    }

    // ── Remove member ─────────────────────────────────────────────────────────

    public function test_owner_can_remove_member_from_organization(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $org    = Organization::factory()->create(['user_id' => $owner->id]);

        $org->members()->attach($member->id, ['role' => 'member']);

        $response = $this->withHeaders($this->authHeader($owner))
            ->deleteJson("/api/organizations/{$org->id}/members/{$member->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Member removed successfully']);

        $this->assertDatabaseMissing('organization_users', [
            'organization_id' => $org->id,
            'user_id'         => $member->id,
        ]);
    }

    // ── Update member role ────────────────────────────────────────────────────

    public function test_owner_can_update_member_role(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $org    = Organization::factory()->create(['user_id' => $owner->id]);

        $org->members()->attach($member->id, ['role' => 'member']);

        $response = $this->withHeaders($this->authHeader($owner))
            ->patchJson("/api/organizations/{$org->id}/members/{$member->id}/role", [
                'role' => 'admin',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Member role updated successfully'])
            ->assertJsonPath('data.role', 'admin');

        $this->assertDatabaseHas('organization_users', [
            'organization_id' => $org->id,
            'user_id'         => $member->id,
            'role'            => 'admin',
        ]);
    }

    public function test_update_member_role_with_invalid_role_returns_validation_error(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $org    = Organization::factory()->create(['user_id' => $owner->id]);

        $org->members()->attach($member->id, ['role' => 'member']);

        $response = $this->withHeaders($this->authHeader($owner))
            ->patchJson("/api/organizations/{$org->id}/members/{$member->id}/role", [
                'role' => 'superadmin',
            ]);

        $response->assertStatus(422);
        $this->assertStringContainsString(
            'role',
            strtolower(implode(' ', $response->json('errors') ?? []))
        );
    }
}
