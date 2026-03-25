<?php

namespace Tests\Feature;

use App\Models\Deployment;
use App\Models\DeploymentProviderCredential;
use App\Models\DeploymentTemplate;
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

class DeploymentTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $token = $user->createToken('api-token')->plainTextToken;

        return ['Authorization' => "Bearer $token"];
    }

    private function createDependencies(User $user): array
    {
        $provider = Provider::create([
            'name'   => 'Provider for Deployment',
            'slug'   => 'aws',
            'type'   => Provider::TYPES[0],
            'status' => Provider::STATUSES[0],
        ]);

        $template = EnvironmentTemplate::create([
            'name'         => 'Deployment Template',
            'slug'         => 'deployment-template-' . uniqid(),
            'description'  => 'Deployment template description',
            'is_public'    => true,
        ]);

        $version = EnvironmentTemplateVersion::create([
            'template_id'     => $template->id,
            'version'         => 'v1',
            'definition_json' => ['nodes' => []],
            'is_active'       => true,
        ]);

        $deploymentTemplate = DeploymentTemplate::create([
            'template_version_id'    => $version->id,
            'custom_parameters_json' => ['size' => 3],
        ]);

        $instanceType = InstanceType::create([
            'provider_id' => $provider->id,
            'name'        => 'm6i.large',
            'vcpus'       => 2,
            'memory_mb'   => 8192,
            'status'      => InstanceType::STATUSES[0],
            'is_active'   => true,
        ]);

        $org         = Organization::factory()->create(['user_id' => $user->id]);
        $project     = Project::factory()->create(['organization_id' => $org->id]);
        $environment = Environment::create([
            'project_id' => $project->id,
            'name'       => 'Environment for deployment',
            'status'     => Environment::STATUSES[0],
        ]);

        return compact('provider', 'deploymentTemplate', 'environment', 'instanceType');
    }

    /** Creates a deployment + its pivot credential row directly (bypasses API). */
    private function createDeploymentWithProvider(
        Environment $environment,
        Provider    $provider,
        array       $attrs = []
    ): Deployment {
        $deployment = Deployment::create(array_merge([
            'environment_id'  => $environment->id,
            'region'          => 'us-east-1',
            'environment_type' => Deployment::ENVIRONMENT_TYPES[0],
            'name'            => 'Test Deployment',
            'status'          => Deployment::STATUSES[1],
        ], $attrs));

        DeploymentProviderCredential::create([
            'deployment_id' => $deployment->id,
            'provider_id'   => $provider->id,
            'provider_slug' => $provider->slug,
        ]);

        return $deployment;
    }

    public function test_user_can_list_deployments_by_environment(): void
    {
        $user = User::factory()->create();
        ['provider' => $provider, 'environment' => $environment] = $this->createDependencies($user);

        $this->createDeploymentWithProvider($environment, $provider, ['name' => 'Primary Deployment']);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/environments/{$environment->id}/deployments");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_create_deployment(): void
    {
        $user = User::factory()->create();
        ['provider' => $provider, 'deploymentTemplate' => $deploymentTemplate, 'environment' => $environment] = $this->createDependencies($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/environments/{$environment->id}/deployments", [
                'deployment_template_id' => $deploymentTemplate->id,
                'provider_credentials'   => [
                    ['provider_id' => $provider->id],
                ],
                'region'           => 'us-west-2',
                'environment_type' => Deployment::ENVIRONMENT_TYPES[0],
                'name'             => 'Created Deployment',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Created Deployment')
            ->assertJsonPath('data.environment_id', (string) $environment->id);

        $this->assertDatabaseHas('deployments', [
            'environment_id' => $environment->id,
            'name'           => 'Created Deployment',
        ]);

        $this->assertDatabaseHas('deployment_provider_credentials', [
            'provider_id' => $provider->id,
        ]);
    }

    public function test_user_can_create_deployment_with_multiple_providers(): void
    {
        $user = User::factory()->create();
        ['environment' => $environment] = $this->createDependencies($user);

        $aws = Provider::create(['name' => 'AWS', 'slug' => 'aws-2', 'type' => Provider::TYPES[0], 'status' => Provider::STATUSES[0]]);
        $gcp = Provider::create(['name' => 'GCP', 'slug' => 'gcp', 'type' => Provider::TYPES[1], 'status' => Provider::STATUSES[0]]);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/environments/{$environment->id}/deployments", [
                'provider_credentials' => [
                    ['provider_id' => $aws->id],
                    ['provider_id' => $gcp->id],
                ],
                'name' => 'Multi-Cloud Deployment',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseCount('deployment_provider_credentials', 2);
    }

    public function test_user_can_delete_deployment(): void
    {
        $user = User::factory()->create();
        ['provider' => $provider, 'environment' => $environment] = $this->createDependencies($user);

        $deployment = $this->createDeploymentWithProvider($environment, $provider, [
            'region'           => 'ap-southeast-1',
            'environment_type' => Deployment::ENVIRONMENT_TYPES[2],
            'name'             => 'Disposable Deployment',
            'status'           => Deployment::STATUSES[2],
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->deleteJson("/api/deployments/{$deployment->id}");

        $response->assertStatus(202);

        $this->assertDatabaseHas('deployments', [
            'id'     => $deployment->id,
            'status' => Deployment::STATUS_DESTROYING,
        ]);
    }

    public function test_create_deployment_requires_provider_credentials(): void
    {
        $user = User::factory()->create();
        ['environment' => $environment] = $this->createDependencies($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/environments/{$environment->id}/deployments", [
                'name' => 'Missing Creds',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['provider_credentials']);
    }
}
