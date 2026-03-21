<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Provider;
use App\Models\ProviderVariableSchema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the org-scoped schema route:
 *   GET /api/organizations/{org}/providers/{provider}/schemas
 *   POST /api/organizations/{org}/providers/{provider}/schemas
 *
 * Schemas are stored globally by provider_slug, so the route resolves the
 * provider record to obtain its slug before querying.
 */
class ProviderVariableSchemaByProviderTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $token = $user->createToken('api-token')->plainTextToken;
        return ['Authorization' => "Bearer $token"];
    }

    private function makeOrg(User $user): Organization
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

    private function makeSchema(array $overrides = []): ProviderVariableSchema
    {
        return ProviderVariableSchema::create(array_merge([
            'provider_slug' => 'gcp',
            'section'       => 'general',
            'name'          => 'gcp_project_id',
            'label'         => 'Project ID',
            'type'          => 'string',
            'required'      => true,
            'is_sensitive'  => false,
            'position'      => 1,
        ], $overrides));
    }

    // ─── List by provider (org-scoped) ────────────────────────────────────────

    public function test_user_can_list_schemas_for_provider(): void
    {
        $user     = User::factory()->create();
        $org      = $this->makeOrg($user);
        $provider = $this->makeProvider($org);

        $this->makeSchema(['provider_slug' => 'gcp', 'name' => 'gcp_project_id', 'position' => 1]);
        $this->makeSchema(['provider_slug' => 'gcp', 'name' => 'gcp_region',     'position' => 2]);
        // A schema for a different slug must not appear
        $this->makeSchema(['provider_slug' => 'aws', 'name' => 'aws_access_key', 'position' => 1]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/organizations/{$org->id}/providers/{$provider->id}/schemas");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        collect($response->json('data'))->each(function (array $schema) {
            $this->assertSame('gcp', $schema['provider_slug']);
        });
    }

    public function test_listing_schemas_for_nonexistent_provider_returns_404(): void
    {
        $user = User::factory()->create();
        $org  = $this->makeOrg($user);

        $this->withHeaders($this->authHeader($user))
            ->getJson("/api/organizations/{$org->id}/providers/99999/schemas")
            ->assertStatus(404);
    }

    public function test_listing_schemas_returns_correct_fields(): void
    {
        $user     = User::factory()->create();
        $org      = $this->makeOrg($user);
        $provider = $this->makeProvider($org);

        $this->makeSchema([
            'provider_slug' => 'gcp',
            'section'       => 'authentication',
            'name'          => 'service_account_json',
            'label'         => 'Service Account JSON',
            'type'          => 'textarea',
            'required'      => true,
            'is_sensitive'  => true,
            'position'      => 1,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/organizations/{$org->id}/providers/{$provider->id}/schemas");

        $response->assertStatus(200)
            ->assertJsonPath('data.0.provider_slug', 'gcp')
            ->assertJsonPath('data.0.section', 'authentication')
            ->assertJsonPath('data.0.name', 'service_account_json')
            ->assertJsonPath('data.0.label', 'Service Account JSON')
            ->assertJsonPath('data.0.type', 'textarea')
            ->assertJsonPath('data.0.required', true)
            ->assertJsonPath('data.0.is_sensitive', true);
    }

    public function test_schemas_are_ordered_by_section_then_position(): void
    {
        $user     = User::factory()->create();
        $org      = $this->makeOrg($user);
        $provider = $this->makeProvider($org, ['slug' => 'slurm', 'name' => 'Slurm']);

        $this->makeSchema(['provider_slug' => 'slurm', 'section' => 'scheduler',  'name' => 'partition',  'position' => 4]);
        $this->makeSchema(['provider_slug' => 'slurm', 'section' => 'connection', 'name' => 'slurm_host', 'position' => 1]);
        $this->makeSchema(['provider_slug' => 'slurm', 'section' => 'connection', 'name' => 'slurm_user', 'position' => 2]);

        $data = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/organizations/{$org->id}/providers/{$provider->id}/schemas")
            ->assertStatus(200)
            ->json('data');

        $this->assertSame('connection', $data[0]['section']);
        $this->assertSame('connection', $data[1]['section']);
        $this->assertSame('scheduler',  $data[2]['section']);
        $this->assertSame('slurm_host', $data[0]['name']);
        $this->assertSame('slurm_user', $data[1]['name']);
    }

    public function test_listing_schemas_returns_empty_when_provider_slug_has_no_schemas(): void
    {
        $user     = User::factory()->create();
        $org      = $this->makeOrg($user);
        $provider = $this->makeProvider($org, ['slug' => 'azure', 'name' => 'Azure']);

        // Schemas exist for gcp but not azure
        $this->makeSchema(['provider_slug' => 'gcp', 'name' => 'gcp_project_id']);

        $this->withHeaders($this->authHeader($user))
            ->getJson("/api/organizations/{$org->id}/providers/{$provider->id}/schemas")
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_select_schema_returns_options_array(): void
    {
        $user     = User::factory()->create();
        $org      = $this->makeOrg($user);
        $provider = $this->makeProvider($org);

        $this->makeSchema([
            'provider_slug' => 'gcp',
            'name'          => 'gcp_region',
            'type'          => 'select',
            'options_json'  => json_encode(['us-central1', 'us-east1']),
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/organizations/{$org->id}/providers/{$provider->id}/schemas");

        $response->assertStatus(200)
            ->assertJsonPath('data.0.options.0', 'us-central1')
            ->assertJsonPath('data.0.options.1', 'us-east1');
    }

    // ─── Create schema (org-scoped) ────────────────────────────────────────────

    public function test_user_can_create_schema_for_provider(): void
    {
        $user     = User::factory()->create();
        $org      = $this->makeOrg($user);
        $provider = $this->makeProvider($org);

        $payload = [
            'section'      => 'authentication',
            'name'         => 'api_key',
            'label'        => 'API Key',
            'type'         => 'secret',
            'required'     => true,
            'is_sensitive' => true,
            'position'     => 1,
        ];

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/organizations/{$org->id}/providers/{$provider->id}/schemas", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.provider_slug', 'gcp')
            ->assertJsonPath('data.name', 'api_key')
            ->assertJsonPath('data.label', 'API Key')
            ->assertJsonPath('data.type', 'secret')
            ->assertJsonPath('data.required', true);

        $this->assertDatabaseHas('provider_variable_schemas', [
            'provider_slug' => 'gcp',
            'name'          => 'api_key',
        ]);
    }

    public function test_creating_schema_for_nonexistent_provider_returns_404(): void
    {
        $user = User::factory()->create();
        $org  = $this->makeOrg($user);

        $this->withHeaders($this->authHeader($user))
            ->postJson("/api/organizations/{$org->id}/providers/99999/schemas", [
                'section'  => 'general',
                'name'     => 'some_field',
                'label'    => 'Some Field',
                'type'     => 'string',
            ])
            ->assertStatus(404);
    }

    // ─── Auth guard ───────────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_list_provider_schemas(): void
    {
        $this->getJson('/api/organizations/1/providers/1/schemas')
            ->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_create_provider_schema(): void
    {
        $this->postJson('/api/organizations/1/providers/1/schemas', [])
            ->assertStatus(401);
    }
}
