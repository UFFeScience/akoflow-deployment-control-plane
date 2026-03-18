<?php

namespace Tests\Feature;

use App\Models\Environment;
use App\Models\EnvironmentTemplate;
use App\Models\EnvironmentTemplateVersion;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnvironmentTest extends TestCase
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

    public function test_user_can_list_environments_by_project(): void
    {
        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        Environment::create(['project_id' => $project->id, 'name' => 'Environment A', 'status' => Environment::STATUSES[0]]);
        Environment::create(['project_id' => $project->id, 'name' => 'Environment B', 'status' => Environment::STATUSES[1]]);
        Environment::create(['project_id' => $project->id, 'name' => 'Environment C', 'status' => Environment::STATUSES[2]]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/environments");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_environment(): void
    {
        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/environments", [
                'name'   => 'New Environment',
                'status' => Environment::STATUSES[1],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Environment')
            ->assertJsonPath('data.project_id', (string) $project->id);

        $this->assertDatabaseHas('environments', [
            'project_id' => $project->id,
            'name'       => 'New Environment',
        ]);
    }

    public function test_user_cannot_list_environments_of_another_users_project(): void
    {
        $userA   = User::factory()->create();
        $userB   = User::factory()->create();
        $project = $this->projectBelongingToUser($userB); // owned by userB

        $response = $this->withHeaders($this->authHeader($userA))
            ->getJson("/api/projects/{$project->id}/environments");

        $response->assertStatus(403);
    }

    public function test_creating_environment_with_template_version_saves_complete_configuration_json(): void
    {
        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        $template = EnvironmentTemplate::create([
            'name'         => 'Test Template',
            'slug'         => 'test-template-' . uniqid(),
            'runtime_type' => 'TEST',
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
                                ['name' => 'project_id', 'type' => 'string', 'required' => true],
                                ['name' => 'region',     'type' => 'string', 'required' => true, 'default' => 'us-central1'],
                                ['name' => 'version',    'type' => 'string', 'required' => true, 'default' => '1.27'],
                            ],
                        ],
                    ],
                ],
                'instance_configurations' => [
                    'worker' => [
                        'sections' => [
                            [
                                'name'   => 'compute',
                                'fields' => [
                                    ['name' => 'machine_type', 'type' => 'string', 'required' => true, 'default' => 'n1-standard-4'],
                                    ['name' => 'disk_size_gb', 'type' => 'number', 'required' => true, 'default' => 100],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // User only provides one field; the rest should come from defaults
        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/environments", [
                'name'                          => 'Complete Config Environment',
                'environment_template_version_id' => $templateVersion->id,
                'configuration_json'             => [
                    'environment_configuration' => ['project_id' => 'my-gcp-project'],
                ],
            ]);

        $response->assertStatus(201);

        $saved = Environment::where('name', 'Complete Config Environment')->firstOrFail();
        $config = $saved->configuration_json;

        // User-provided value must be present
        $this->assertEquals('my-gcp-project', $config['environment_configuration']['project_id']);

        // Defaults from the template definition must also be present
        $this->assertEquals('us-central1', $config['environment_configuration']['region']);
        $this->assertEquals('1.27', $config['environment_configuration']['version']);

        // instance_configurations defaults must be present too
        $this->assertEquals('n1-standard-4', $config['instance_configurations']['worker']['machine_type']);
        $this->assertEquals(100, $config['instance_configurations']['worker']['disk_size_gb']);
    }

    public function test_user_provided_values_override_template_defaults_in_configuration_json(): void
    {
        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        $template = EnvironmentTemplate::create([
            'name'         => 'Override Template',
            'slug'         => 'override-template-' . uniqid(),
            'runtime_type' => 'TEST',
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
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // User overrides region but not version
        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/environments", [
                'name'                          => 'Override Environment',
                'environment_template_version_id' => $templateVersion->id,
                'configuration_json'             => [
                    'environment_configuration' => ['region' => 'europe-west1'],
                ],
            ]);

        $response->assertStatus(201);

        $saved  = Environment::where('name', 'Override Environment')->firstOrFail();
        $config = $saved->configuration_json;

        // User-supplied value must win over the default
        $this->assertEquals('europe-west1', $config['environment_configuration']['region']);

        // Non-overridden field must still carry the default
        $this->assertEquals('1.27', $config['environment_configuration']['version']);
    }
}
