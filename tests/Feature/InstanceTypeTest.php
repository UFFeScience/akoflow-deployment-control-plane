<?php

namespace Tests\Feature;

use App\Enums\InstanceTypeStatus;
use App\Models\InstanceType;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstanceTypeTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $token = $user->createToken('api-token')->plainTextToken;

        return ['Authorization' => "Bearer $token"];
    }

    private function createProvider(): Provider
    {
        return Provider::create([
            'name' => 'Provider X',
            'type' => Provider::TYPES[0],
            'status' => Provider::STATUSES[0],
        ]);
    }

    public function test_user_can_list_instance_types(): void
    {
        $user = User::factory()->create();
        $provider = $this->createProvider();
        InstanceType::create([
            'provider_id' => $provider->id,
            'name' => 'c5.large',
            'status' => InstanceType::STATUSES[0],
        ]);
        InstanceType::create([
            'provider_id' => $provider->id,
            'name' => 'c5.xlarge',
            'status' => InstanceType::STATUSES[1],
        ]);
        InstanceType::create([
            'provider_id' => $provider->id,
            'name' => 'g4dn.xlarge',
            'status' => InstanceType::STATUSES[2],
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/instance-types');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_instance_type(): void
    {
        $user = User::factory()->create();
        $provider = $this->createProvider();

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/instance-types', [
                'provider_id' => $provider->id,
                'name' => 'm5.large',
                'vcpus' => 4,
                'memory_mb' => 8192,
                'status' => InstanceType::STATUSES[0],
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'm5.large')
            ->assertJsonPath('data.provider_id', $provider->id);

        $this->assertDatabaseHas('instance_types', [
            'name' => 'm5.large',
            'provider_id' => $provider->id,
        ]);
    }

    public function test_user_can_update_instance_type_status(): void
    {
        $user = User::factory()->create();
        $provider = $this->createProvider();
        $instanceType = InstanceType::create([
            'provider_id' => $provider->id,
            'name' => 't3.small',
            'status' => InstanceType::STATUSES[0],
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->patchJson("/api/instance-types/{$instanceType->id}/status", [
                'status' => InstanceTypeStatus::UNAVAILABLE->value,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', InstanceTypeStatus::UNAVAILABLE->value);

        $this->assertDatabaseHas('instance_types', [
            'id' => $instanceType->id,
            'status' => InstanceTypeStatus::UNAVAILABLE->value,
        ]);
    }
}
