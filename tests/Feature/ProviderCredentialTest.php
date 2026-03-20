<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Provider;
use App\Models\ProviderCredential;
use App\Models\ProviderCredentialValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderCredentialTest extends TestCase
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

    // ─── List credentials ─────────────────────────────────────────────────────

    public function test_user_can_list_credentials_for_provider(): void
    {
        $user     = User::factory()->create();
        $org      = $this->makeOrganization($user);
        $provider = $this->makeProvider($org);

        $cred = ProviderCredential::create([
            'provider_id' => $provider->id,
            'name'        => 'Prod Key',
            'slug'        => 'prod-key',
            'is_active'   => true,
        ]);

        ProviderCredentialValue::create([
            'provider_credential_id' => $cred->id,
            'field_key'              => 'gcp_project_id',
            'field_value'            => 'my-project',
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/organizations/{$org->id}/providers/{$provider->id}/credentials");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Prod Key')
            ->assertJsonPath('data.0.is_active', true);
    }

    public function test_listing_credentials_of_nonexistent_provider_returns_404(): void
    {
        $user = User::factory()->create();
        $org  = $this->makeOrganization($user);

        $this->withHeaders($this->authHeader($user))
            ->getJson("/api/organizations/{$org->id}/providers/99999/credentials")
            ->assertStatus(404);
    }

    public function test_listing_credentials_does_not_return_values_from_other_provider(): void
    {
        $user  = User::factory()->create();
        $org   = $this->makeOrganization($user);
        $provA = $this->makeProvider($org, ['name' => 'GCP', 'slug' => 'gcp']);
        $provB = $this->makeProvider($org, ['name' => 'AWS', 'slug' => 'aws']);

        ProviderCredential::create([
            'provider_id' => $provB->id,
            'name'        => 'AWS Cred',
            'slug'        => 'aws-cred',
            'is_active'   => true,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/organizations/{$org->id}/providers/{$provA->id}/credentials");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    // ─── Create credential ────────────────────────────────────────────────────

    public function test_user_can_create_credential_with_values(): void
    {
        $user     = User::factory()->create();
        $org      = $this->makeOrganization($user);
        $provider = $this->makeProvider($org);

        $payload = [
            'name'        => 'Staging Key',
            'description' => 'Used for staging',
            'is_active'   => true,
            'values'      => [
                'gcp_project_id' => 'my-staging-project',
                'gcp_region'     => 'us-central1',
            ],
        ];

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/organizations/{$org->id}/providers/{$provider->id}/credentials", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Staging Key')
            ->assertJsonPath('data.description', 'Used for staging')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('provider_credentials', [
            'provider_id' => $provider->id,
            'name'        => 'Staging Key',
        ]);

        $this->assertDatabaseHas('provider_credential_values', [
            'field_key'   => 'gcp_project_id',
            'field_value' => 'my-staging-project',
        ]);

        $this->assertDatabaseHas('provider_credential_values', [
            'field_key'   => 'gcp_region',
            'field_value' => 'us-central1',
        ]);
    }

    public function test_creating_credential_requires_name(): void
    {
        $user     = User::factory()->create();
        $org      = $this->makeOrganization($user);
        $provider = $this->makeProvider($org);

        $this->withHeaders($this->authHeader($user))
            ->postJson("/api/organizations/{$org->id}/providers/{$provider->id}/credentials", [
                'values' => ['key' => 'value'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_creating_credential_requires_values(): void
    {
        $user     = User::factory()->create();
        $org      = $this->makeOrganization($user);
        $provider = $this->makeProvider($org);

        $this->withHeaders($this->authHeader($user))
            ->postJson("/api/organizations/{$org->id}/providers/{$provider->id}/credentials", [
                'name' => 'My Key',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['values']);
    }

    public function test_creating_credential_for_nonexistent_provider_returns_404(): void
    {
        $user = User::factory()->create();
        $org  = $this->makeOrganization($user);

        $this->withHeaders($this->authHeader($user))
            ->postJson("/api/organizations/{$org->id}/providers/99999/credentials", [
                'name'   => 'Key',
                'values' => ['some_field' => 'some_value'],
            ])
            ->assertStatus(404);
    }

    // ─── Delete credential ────────────────────────────────────────────────────

    public function test_user_can_delete_credential(): void
    {
        $user     = User::factory()->create();
        $org      = $this->makeOrganization($user);
        $provider = $this->makeProvider($org);
        $cred     = ProviderCredential::create([
            'provider_id' => $provider->id,
            'name'        => 'Old Key',
            'slug'        => 'old-key',
            'is_active'   => true,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->deleteJson("/api/organizations/{$org->id}/providers/{$provider->id}/credentials/{$cred->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Credential deleted successfully');

        $this->assertDatabaseMissing('provider_credentials', ['id' => $cred->id]);
    }

    public function test_deleting_credential_belonging_to_different_provider_returns_404(): void
    {
        $user  = User::factory()->create();
        $org   = $this->makeOrganization($user);
        $provA = $this->makeProvider($org, ['name' => 'GCP', 'slug' => 'gcp']);
        $provB = $this->makeProvider($org, ['name' => 'AWS', 'slug' => 'aws']);

        $credB = ProviderCredential::create([
            'provider_id' => $provB->id,
            'name'        => 'AWS Key',
            'slug'        => 'aws-key',
            'is_active'   => true,
        ]);

        // Attempt to delete provB's credential via provA's route
        $this->withHeaders($this->authHeader($user))
            ->deleteJson("/api/organizations/{$org->id}/providers/{$provA->id}/credentials/{$credB->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('provider_credentials', ['id' => $credB->id]);
    }

    public function test_deleting_nonexistent_credential_returns_404(): void
    {
        $user     = User::factory()->create();
        $org      = $this->makeOrganization($user);
        $provider = $this->makeProvider($org);

        $this->withHeaders($this->authHeader($user))
            ->deleteJson("/api/organizations/{$org->id}/providers/{$provider->id}/credentials/99999")
            ->assertStatus(404);
    }

    // ─── Auth guard ───────────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_access_credentials(): void
    {
        $user     = User::factory()->create();
        $org      = $this->makeOrganization($user);
        $provider = $this->makeProvider($org);
        $this->getJson("/api/organizations/{$org->id}/providers/{$provider->id}/credentials")->assertStatus(401);
    }
}
