<?php

namespace Tests\Feature;

use App\Enums\HealthStatus;
use App\Models\Organization;
use App\Models\Provider;
use App\Models\User;
use App\Services\CheckProviderHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $token = $user->createToken('api-token')->plainTextToken;

        return ['Authorization' => "Bearer $token"];
    }

    private function makeOrganization(User $user): Organization
    {
        return Organization::factory()->create(['user_id' => $user->id]);
    }

    private function makeProvider(Organization $org, array $overrides = []): Provider
    {
        return Provider::create(array_merge([
            'organization_id' => $org->id,
            'name'            => 'GCP',
            'slug'            => 'gcp',
            'type'            => Provider::TYPES[0],
            'status'          => Provider::STATUSES[0],
        ], $overrides));
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    public function test_user_can_list_providers(): void
    {
        $user = User::factory()->create();
        $org  = $this->makeOrganization($user);

        $this->makeProvider($org, ['name' => 'AWS', 'slug' => 'aws', 'type' => Provider::TYPES[0], 'status' => Provider::STATUSES[0]]);
        $this->makeProvider($org, ['name' => 'GCP', 'slug' => 'gcp', 'type' => Provider::TYPES[1], 'status' => Provider::STATUSES[1]]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/organizations/{$org->id}/providers");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_list_providers_only_returns_providers_of_the_organization(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $orgA  = $this->makeOrganization($userA);
        $orgB  = $this->makeOrganization($userB);

        $this->makeProvider($orgA, ['name' => 'AWS', 'slug' => 'aws']);
        $this->makeProvider($orgB, ['name' => 'GCP', 'slug' => 'gcp']);

        $response = $this->withHeaders($this->authHeader($userA))
            ->getJson("/api/organizations/{$orgA->id}/providers");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'AWS');
    }

    public function test_list_providers_includes_credentials_count(): void
    {
        $user = User::factory()->create();
        $org  = $this->makeOrganization($user);
        $this->makeProvider($org);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/organizations/{$org->id}/providers");

        $response->assertStatus(200)
            ->assertJsonPath('data.0.credentials_count', 0);
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    public function test_user_can_get_provider_by_id(): void
    {
        $user     = User::factory()->create();
        $org      = $this->makeOrganization($user);
        $provider = $this->makeProvider($org, [
            'name'        => 'GCP',
            'slug'        => 'gcp',
            'description' => 'Google Cloud',
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/organizations/{$org->id}/providers/{$provider->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $provider->id)
            ->assertJsonPath('data.organization_id', $org->id)
            ->assertJsonPath('data.name', 'GCP')
            ->assertJsonPath('data.slug', 'gcp')
            ->assertJsonPath('data.description', 'Google Cloud')
            ->assertJsonPath('data.credentials_count', 0);
    }

    public function test_getting_provider_from_another_organization_returns_404(): void
    {
        $userA    = User::factory()->create();
        $userB    = User::factory()->create();
        $orgA     = $this->makeOrganization($userA);
        $orgB     = $this->makeOrganization($userB);
        $provider = $this->makeProvider($orgB);

        $response = $this->withHeaders($this->authHeader($userA))
            ->getJson("/api/organizations/{$orgA->id}/providers/{$provider->id}");

        $response->assertStatus(404);
    }

    public function test_getting_nonexistent_provider_returns_404(): void
    {
        $user = User::factory()->create();
        $org  = $this->makeOrganization($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/organizations/{$org->id}/providers/99999");

        $response->assertStatus(404);
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    public function test_user_can_create_provider(): void
    {
        $user = User::factory()->create();
        $org  = $this->makeOrganization($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/organizations/{$org->id}/providers", [
                'name'   => 'On Prem',
                'type'   => Provider::TYPES[2],
                'status' => Provider::STATUSES[0],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'On Prem')
            ->assertJsonPath('data.type', Provider::TYPES[2])
            ->assertJsonPath('data.organization_id', $org->id);

        $this->assertDatabaseHas('providers', [
            'organization_id' => $org->id,
            'name'            => 'On Prem',
            'type'            => Provider::TYPES[2],
        ]);
    }

    public function test_user_can_create_provider_with_slug_and_description(): void
    {
        $user = User::factory()->create();
        $org  = $this->makeOrganization($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/organizations/{$org->id}/providers", [
                'name'        => 'AWS Production',
                'slug'        => 'aws',
                'description' => 'Amazon Web Services',
                'type'        => Provider::TYPES[0],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', 'aws')
            ->assertJsonPath('data.description', 'Amazon Web Services');

        $this->assertDatabaseHas('providers', ['organization_id' => $org->id, 'slug' => 'aws']);
    }

    public function test_same_slug_can_exist_in_different_organizations(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $orgA  = $this->makeOrganization($userA);
        $orgB  = $this->makeOrganization($userB);

        $this->withHeaders($this->authHeader($userA))
            ->postJson("/api/organizations/{$orgA->id}/providers", [
                'name' => 'AWS A',
                'slug' => 'aws',
                'type' => Provider::TYPES[0],
            ])->assertStatus(201);

        $this->withHeaders($this->authHeader($userB))
            ->postJson("/api/organizations/{$orgB->id}/providers", [
                'name' => 'AWS B',
                'slug' => 'aws',
                'type' => Provider::TYPES[0],
            ])->assertStatus(201);
    }

    public function test_creating_provider_with_duplicate_slug_in_same_org_fails(): void
    {
        $user = User::factory()->create();
        $org  = $this->makeOrganization($user);
        $this->makeProvider($org, ['name' => 'GCP', 'slug' => 'gcp']);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/organizations/{$org->id}/providers", [
                'name' => 'GCP Duplicate',
                'slug' => 'gcp',
                'type' => Provider::TYPES[0],
            ]);

        $response->assertStatus(422);
    }

    // ─── Health ───────────────────────────────────────────────────────────────

    public function test_user_can_update_provider_health(): void
    {
        $user     = User::factory()->create();
        $org      = $this->makeOrganization($user);
        $provider = $this->makeProvider($org, ['name' => 'Health Provider', 'slug' => null]);

        $payload = [
            'health_status'        => HealthStatus::UNHEALTHY->value,
            'health_message'       => 'Timeouts detected',
            'last_health_check_at' => now()->toISOString(),
        ];

        $response = $this->withHeaders($this->authHeader($user))
            ->patchJson("/api/organizations/{$org->id}/providers/{$provider->id}/health", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.health_status', HealthStatus::UNHEALTHY->value)
            ->assertJsonPath('data.health_message', 'Timeouts detected');

        $this->assertDatabaseHas('providers', [
            'id'             => $provider->id,
            'health_status'  => HealthStatus::UNHEALTHY->value,
            'health_message' => 'Timeouts detected',
        ]);
    }

    public function test_user_can_run_health_check(): void
    {
        $user     = User::factory()->create();
        $org      = $this->makeOrganization($user);
        $provider = $this->makeProvider($org, ['name' => 'HealthCheck Provider', 'slug' => 'aws', 'type' => Provider::TYPES[0]]);

        $mock = \Mockery::mock(CheckProviderHealthService::class);
        $mock->shouldReceive('handle')
            ->once()
            ->with((string) $provider->id, (string) $org->id)
            ->andReturn($provider);

        $this->app->instance(CheckProviderHealthService::class, $mock);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/organizations/{$org->id}/providers/{$provider->id}/health/check");

        $response->assertStatus(201)
            ->assertJsonPath('data.id', $provider->id);
    }

    // ─── Auth guard ───────────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_list_providers(): void
    {
        $this->getJson('/api/organizations/1/providers')->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_get_provider(): void
    {
        $user     = User::factory()->create();
        $org      = $this->makeOrganization($user);
        $provider = $this->makeProvider($org);

        $this->getJson("/api/organizations/{$org->id}/providers/{$provider->id}")->assertStatus(401);
    }
}
