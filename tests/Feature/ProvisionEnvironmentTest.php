<?php

namespace Tests\Feature;

use App\Models\Cluster;
use App\Models\Environment;
use App\Models\EnvironmentTemplate;
use App\Models\EnvironmentTemplateVersion;
use App\Models\InstanceType;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProvisionEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $token = $user->createToken('api-token')->plainTextToken;

        return ['Authorization' => "Bearer $token"];
    }

    private function projectBelongingToUser(User $user): Project
    {
        $org = Organization::factory()->create(['user_id' => $user->id]);

        return Project::factory()->create(['organization_id' => $org->id]);
    }

    private function createProvider(): Provider
    {
        return Provider::create([
            'name'   => 'Test Provider',
            'type'   => Provider::TYPES[0],
            'status' => Provider::STATUSES[0],
        ]);
    }

    private function createInstanceType(Provider $provider): InstanceType
    {
        return InstanceType::create([
            'provider_id' => $provider->id,
            'name'        => 'm6i.large',
            'vcpus'       => 2,
            'memory_mb'   => 8192,
            'status'      => InstanceType::STATUSES[0],
            'is_active'   => true,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Happy path — environment only (no cluster)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_provision_creates_environment_without_cluster(): void
    {
        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/environments/provision", [
                'name'           => 'Provision Env Only',
                'execution_mode' => 'manual',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Provision Env Only')
            ->assertJsonPath('data.project_id', (string) $project->id);

        $this->assertDatabaseHas('environments', [
            'project_id' => $project->id,
            'name'       => 'Provision Env Only',
        ]);

        // No cluster should have been created
        $this->assertDatabaseCount('clusters', 0);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Happy path — environment + cluster in a single request
    // ──────────────────────────────────────────────────────────────────────────

    public function test_provision_creates_environment_and_cluster_atomically(): void
    {
        $user         = User::factory()->create();
        $project      = $this->projectBelongingToUser($user);
        $provider     = $this->createProvider();
        $instanceType = $this->createInstanceType($provider);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/environments/provision", [
                'name'           => 'Full Provision',
                'description'    => 'Environment with cluster',
                'execution_mode' => 'manual',
                'cluster' => [
                    'provider_id' => $provider->id,
                    'region'      => 'us-east-1',
                    'instance_groups' => [
                        [
                            'instance_type_id' => $instanceType->id,
                            'role'             => 'worker',
                            'quantity'         => 2,
                        ],
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Full Provision')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'project_id',
                    'cluster' => [
                        'id',
                        'environment_id',
                        'provider_id',
                        'region',
                        'instance_groups',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('environments', [
            'project_id' => $project->id,
            'name'       => 'Full Provision',
        ]);

        $environmentId = $response->json('data.id');
        $this->assertDatabaseHas('clusters', [
            'environment_id' => $environmentId,
            'provider_id'    => $provider->id,
            'region'         => 'us-east-1',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Cluster with multiple instance groups
    // ──────────────────────────────────────────────────────────────────────────

    public function test_provision_creates_cluster_with_multiple_instance_groups(): void
    {
        $user         = User::factory()->create();
        $project      = $this->projectBelongingToUser($user);
        $provider     = $this->createProvider();
        $instanceType = $this->createInstanceType($provider);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/environments/provision", [
                'name' => 'Multi-Group Provision',
                'cluster' => [
                    'provider_id' => $provider->id,
                    'region'      => 'us-west-2',
                    'instance_groups' => [
                        [
                            'instance_type_id' => $instanceType->id,
                            'role'             => 'master',
                            'quantity'         => 1,
                        ],
                        [
                            'instance_type_id' => $instanceType->id,
                            'role'             => 'worker',
                            'quantity'         => 3,
                        ],
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonCount(2, 'data.cluster.instance_groups');

        // 1 master + 3 workers = 4 instances
        $this->assertDatabaseCount('provisioned_instances', 4);

        $this->assertDatabaseHas('instance_groups', ['role' => 'master', 'quantity' => 1]);
        $this->assertDatabaseHas('instance_groups', ['role' => 'worker', 'quantity' => 3]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // configuration_json is merged with template defaults
    // ──────────────────────────────────────────────────────────────────────────

    public function test_provision_merges_template_defaults_into_configuration_json(): void
    {
        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        $template = EnvironmentTemplate::create([
            'name'         => 'K8s Template',
            'slug'         => 'k8s-template-' . uniqid(),
            'is_public'    => true,
        ]);

        $templateVersion = EnvironmentTemplateVersion::create([
            'template_id'     => $template->id,
            'version'         => '1.0.0',
            'is_active'       => true,
            'definition_json' => [
                'environment_configuration' => [
                    'sections' => [
                        [
                            'name'   => 'general',
                            'fields' => [
                                ['name' => 'region',  'type' => 'string', 'default' => 'us-central1'],
                                ['name' => 'version', 'type' => 'string', 'default' => '1.27'],
                                ['name' => 'project', 'type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/environments/provision", [
                'name'                           => 'Templated Env',
                'environment_template_version_id' => $templateVersion->id,
                'configuration_json' => [
                    'environment_configuration' => ['project' => 'my-gcp-project'],
                ],
            ]);

        $response->assertStatus(201);

        $saved  = Environment::where('name', 'Templated Env')->firstOrFail();
        $config = $saved->configuration_json;

        // User-provided value
        $this->assertEquals('my-gcp-project', $config['environment_configuration']['project']);

        // Defaults from template must be merged in
        $this->assertEquals('us-central1', $config['environment_configuration']['region']);
        $this->assertEquals('1.27',        $config['environment_configuration']['version']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Validation failures
    // ──────────────────────────────────────────────────────────────────────────

    public function test_provision_requires_name(): void
    {
        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/environments/provision", [
                'description' => 'Missing name',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_provision_requires_provider_id_when_cluster_key_is_present(): void
    {
        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/environments/provision", [
                'name'    => 'Env missing provider',
                'cluster' => [
                    'region' => 'us-east-1',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cluster.provider_id']);
    }

    public function test_provision_rejects_non_existent_provider(): void
    {
        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/environments/provision", [
                'name' => 'Bad Provider Env',
                'cluster' => [
                    'provider_id' => 9999,
                    'region'      => 'us-east-1',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cluster.provider_id']);
    }

    public function test_provision_rejects_non_existent_instance_type_in_cluster(): void
    {
        $user     = User::factory()->create();
        $project  = $this->projectBelongingToUser($user);
        $provider = $this->createProvider();

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/environments/provision", [
                'name' => 'Bad Instance Type Env',
                'cluster' => [
                    'provider_id'     => $provider->id,
                    'region'          => 'us-east-1',
                    'instance_groups' => [
                        [
                            'instance_type_id' => 9999,
                            'role'             => 'worker',
                            'quantity'         => 1,
                        ],
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cluster.instance_groups.0.instance_type_id']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Authorization
    // ──────────────────────────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_provision(): void
    {
        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        $response = $this->postJson("/api/projects/{$project->id}/environments/provision", [
            'name' => 'Unauth Env',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_cannot_provision_environment_in_another_users_project(): void
    {
        $userA   = User::factory()->create();
        $userB   = User::factory()->create();
        $project = $this->projectBelongingToUser($userB);

        $response = $this->withHeaders($this->authHeader($userA))
            ->postJson("/api/projects/{$project->id}/environments/provision", [
                'name' => 'Unauthorized Env',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseCount('environments', 0);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Atomicity — if cluster creation fails, environment must not be persisted
    // ──────────────────────────────────────────────────────────────────────────

    public function test_environment_is_not_persisted_when_cluster_data_is_invalid(): void
    {
        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        // Sending a cluster block with a non-existent provider triggers
        // a 422 validation error before any DB write happens
        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/environments/provision", [
                'name' => 'Should Not Persist',
                'cluster' => [
                    'provider_id' => 99999,
                    'region'      => 'us-east-1',
                ],
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('environments', 0);
        $this->assertDatabaseCount('clusters', 0);
    }
}
