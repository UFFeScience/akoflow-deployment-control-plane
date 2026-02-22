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
}
