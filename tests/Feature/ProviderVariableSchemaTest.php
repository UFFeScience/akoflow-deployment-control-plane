<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\ProviderVariableSchema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderVariableSchemaTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $token = $user->createToken('api-token')->plainTextToken;
        return ['Authorization' => "Bearer $token"];
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

    // ─── List all ─────────────────────────────────────────────────────────────

    public function test_user_can_list_all_schemas(): void
    {
        $user = User::factory()->create();
        $this->makeSchema(['provider_slug' => 'gcp', 'name' => 'gcp_project_id']);
        $this->makeSchema(['provider_slug' => 'aws', 'name' => 'aws_region', 'section' => 'general']);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/provider-type-schemas');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_list_all_schemas_returns_correct_fields(): void
    {
        $user = User::factory()->create();
        $this->makeSchema([
            'provider_slug' => 'gcp',
            'name'          => 'gcp_project_id',
            'label'         => 'Project ID',
            'required'      => true,
            'is_sensitive'  => false,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/provider-type-schemas');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.provider_slug', 'gcp')
            ->assertJsonPath('data.0.name', 'gcp_project_id')
            ->assertJsonPath('data.0.label', 'Project ID')
            ->assertJsonPath('data.0.required', true)
            ->assertJsonPath('data.0.is_sensitive', false);
    }

    // ─── List by slug ─────────────────────────────────────────────────────────

    public function test_user_can_list_schemas_by_slug(): void
    {
        $user = User::factory()->create();
        $this->makeSchema(['provider_slug' => 'gcp', 'name' => 'gcp_project_id', 'position' => 1]);
        $this->makeSchema(['provider_slug' => 'gcp', 'name' => 'gcp_region',     'position' => 2]);
        $this->makeSchema(['provider_slug' => 'aws', 'name' => 'aws_region',     'position' => 1]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/provider-type-schemas/gcp');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // All returned schemas must belong to gcp
        collect($response->json('data'))->each(function (array $schema) {
            $this->assertSame('gcp', $schema['provider_slug']);
        });
    }

    public function test_listing_schemas_by_unknown_slug_returns_empty_collection(): void
    {
        $user = User::factory()->create();
        $this->makeSchema(['provider_slug' => 'gcp', 'name' => 'gcp_project_id']);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/provider-type-schemas/nonexistent-slug');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_schemas_by_slug_are_ordered_by_section_then_position(): void
    {
        $user = User::factory()->create();
        $this->makeSchema(['provider_slug' => 'slurm', 'section' => 'scheduler', 'name' => 'partition', 'position' => 4]);
        $this->makeSchema(['provider_slug' => 'slurm', 'section' => 'connection', 'name' => 'slurm_host', 'position' => 1]);
        $this->makeSchema(['provider_slug' => 'slurm', 'section' => 'connection', 'name' => 'slurm_user', 'position' => 2]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/provider-type-schemas/slurm');

        $data = $response->assertStatus(200)->json('data');

        // connection section comes before scheduler lexicographically
        $this->assertSame('connection', $data[0]['section']);
        $this->assertSame('connection', $data[1]['section']);
        $this->assertSame('scheduler', $data[2]['section']);
        // within connection, ordered by position
        $this->assertSame('slurm_host', $data[0]['name']);
        $this->assertSame('slurm_user', $data[1]['name']);
    }

    public function test_select_schema_returns_options_array(): void
    {
        $user = User::factory()->create();
        $this->makeSchema([
            'provider_slug' => 'gcp',
            'name'          => 'gcp_region',
            'type'          => 'select',
            'options_json'  => json_encode(['us-central1', 'us-east1']),
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/provider-type-schemas/gcp');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.options.0', 'us-central1')
            ->assertJsonPath('data.0.options.1', 'us-east1');
    }

    // ─── Auth guard ───────────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_list_schemas(): void
    {
        $this->getJson('/api/provider-type-schemas')->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_list_schemas_by_slug(): void
    {
        $this->getJson('/api/provider-type-schemas/gcp')->assertStatus(401);
    }
}
