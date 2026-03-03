<?php

namespace Tests\Feature;

use App\Enums\HealthStatus;
use App\Models\Provider;
use App\Models\User;
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

    // ─── List ─────────────────────────────────────────────────────────────────

    public function test_user_can_list_providers(): void
    {
        $user = User::factory()->create();
        Provider::create([
            'name' => 'AWS',
            'type' => Provider::TYPES[0],
            'status' => Provider::STATUSES[0],
        ]);
        Provider::create([
            'name' => 'GCP',
            'type' => Provider::TYPES[1],
            'status' => Provider::STATUSES[1],
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/providers');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_list_providers_includes_credentials_count(): void
    {
        $user = User::factory()->create();
        Provider::create([
            'name'   => 'GCP',
            'slug'   => 'gcp',
            'type'   => Provider::TYPES[0],
            'status' => Provider::STATUSES[0],
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/providers');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.credentials_count', 0);
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    public function test_user_can_get_provider_by_id(): void
    {
        $user     = User::factory()->create();
        $provider = Provider::create([
            'name'        => 'GCP',
            'slug'        => 'gcp',
            'description' => 'Google Cloud',
            'type'        => Provider::TYPES[0],
            'status'      => Provider::STATUSES[0],
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/providers/{$provider->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $provider->id)
            ->assertJsonPath('data.name', 'GCP')
            ->assertJsonPath('data.slug', 'gcp')
            ->assertJsonPath('data.description', 'Google Cloud')
            ->assertJsonPath('data.credentials_count', 0);
    }

    public function test_getting_nonexistent_provider_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/providers/99999');

        $response->assertStatus(404);
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    public function test_user_can_create_provider(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/providers', [
                'name' => 'On Prem',
                'type' => Provider::TYPES[2],
                'status' => Provider::STATUSES[0],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'On Prem')
            ->assertJsonPath('data.type', Provider::TYPES[2]);

        $this->assertDatabaseHas('providers', [
            'name' => 'On Prem',
            'type' => Provider::TYPES[2],
        ]);
    }

    public function test_user_can_create_provider_with_slug_and_description(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/providers', [
                'name'        => 'AWS Production',
                'slug'        => 'aws',
                'description' => 'Amazon Web Services',
                'type'        => Provider::TYPES[0],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', 'aws')
            ->assertJsonPath('data.description', 'Amazon Web Services');

        $this->assertDatabaseHas('providers', ['slug' => 'aws']);
    }

    public function test_creating_provider_with_duplicate_slug_fails(): void
    {
        $user = User::factory()->create();
        Provider::create([
            'name'   => 'GCP',
            'slug'   => 'gcp',
            'type'   => Provider::TYPES[0],
            'status' => Provider::STATUSES[0],
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/providers', [
                'name' => 'GCP Duplicate',
                'slug' => 'gcp',
                'type' => Provider::TYPES[0],
            ]);

        $response->assertStatus(422);
    }

    // ─── Health ───────────────────────────────────────────────────────────────

    public function test_user_can_update_provider_health(): void
    {
        $user = User::factory()->create();
        $provider = Provider::create([
            'name' => 'Health Provider',
            'type' => Provider::TYPES[0],
            'status' => Provider::STATUSES[0],
        ]);

        $payload = [
            'health_status' => HealthStatus::UNHEALTHY->value,
            'health_message' => 'Timeouts detected',
            'last_health_check_at' => now()->toISOString(),
        ];

        $response = $this->withHeaders($this->authHeader($user))
            ->patchJson("/api/providers/{$provider->id}/health", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.health_status', HealthStatus::UNHEALTHY->value)
            ->assertJsonPath('data.health_message', 'Timeouts detected');

        $this->assertDatabaseHas('providers', [
            'id' => $provider->id,
            'health_status' => HealthStatus::UNHEALTHY->value,
            'health_message' => 'Timeouts detected',
        ]);
    }

    // ─── Auth guard ───────────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_list_providers(): void
    {
        $this->getJson('/api/providers')->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_get_provider(): void
    {
        $provider = Provider::create([
            'name'   => 'GCP',
            'type'   => Provider::TYPES[0],
            'status' => Provider::STATUSES[0],
        ]);

        $this->getJson("/api/providers/{$provider->id}")->assertStatus(401);
    }
}
