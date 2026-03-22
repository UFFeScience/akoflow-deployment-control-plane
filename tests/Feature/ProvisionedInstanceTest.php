<?php

namespace Tests\Feature;

use App\Models\Deployment;
use App\Models\DeploymentTemplate;
use App\Models\Environment;
use App\Models\EnvironmentTemplate;
use App\Models\EnvironmentTemplateVersion;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Provider;
use App\Models\ProvisionedResource;
use App\Models\RunLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProvisionedInstanceTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $token = $user->createToken('api-token')->plainTextToken;

        return ['Authorization' => "Bearer $token"];
    }

    private function createDeploymentForUser(User $user): Deployment
    {
        $provider = Provider::create([
            'name'   => 'Resource Provider',
            'type'   => Provider::TYPES[0],
            'status' => Provider::STATUSES[0],
        ]);

        $template = EnvironmentTemplate::create([
            'name'         => 'Resource Template',
            'slug'         => 'resource-template-' . uniqid(),
            'description'  => 'Template for resources',
            'is_public'    => true,
        ]);

        $version = EnvironmentTemplateVersion::create([
            'template_id'     => $template->id,
            'version'         => 'v1',
            'definition_json' => ['steps' => []],
            'is_active'       => true,
        ]);

        $deploymentTemplate = DeploymentTemplate::create([
            'template_version_id'    => $version->id,
            'custom_parameters_json' => ['nodes' => 2],
        ]);

        $org         = Organization::factory()->create(['user_id' => $user->id]);
        $project     = Project::factory()->create(['organization_id' => $org->id]);
        $environment = Environment::create([
            'project_id' => $project->id,
            'name'       => 'Resource Environment',
            'status'     => Environment::STATUSES[0],
        ]);

        return Deployment::create([
            'environment_id'         => $environment->id,
            'deployment_template_id' => $deploymentTemplate->id,
            'provider_id'            => $provider->id,
            'region'                 => 'us-central1',
            'environment_type'       => Deployment::ENVIRONMENT_TYPES[0],
            'name'                   => 'Deployment for resources',
            'status'                 => Deployment::STATUSES[1],
        ]);
    }

    public function test_user_can_list_resources_by_deployment(): void
    {
        $user       = User::factory()->create();
        $deployment = $this->createDeploymentForUser($user);

        ProvisionedResource::create([
            'deployment_id'        => $deployment->id,
            'provider_resource_id' => 'res-abc-1',
            'name'                 => 'compute-node-1',
            'status'               => ProvisionedResource::STATUS_RUNNING,
        ]);
        ProvisionedResource::create([
            'deployment_id'        => $deployment->id,
            'provider_resource_id' => 'res-abc-2',
            'name'                 => 'compute-node-2',
            'status'               => ProvisionedResource::STATUS_RUNNING,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/deployments/{$deployment->id}/resources");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_get_resource_details(): void
    {
        $user       = User::factory()->create();
        $deployment = $this->createDeploymentForUser($user);

        $resource = ProvisionedResource::create([
            'deployment_id'        => $deployment->id,
            'provider_resource_id' => 'res-xyz-789',
            'name'                 => 'storage-node',
            'status'               => ProvisionedResource::STATUS_RUNNING,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/resources/{$resource->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $resource->id)
            ->assertJsonPath('data.deployment_id', $deployment->id);
    }

    public function test_user_can_list_resource_logs(): void
    {
        $user       = User::factory()->create();
        $deployment = $this->createDeploymentForUser($user);

        $resource = ProvisionedResource::create([
            'deployment_id'        => $deployment->id,
            'provider_resource_id' => 'res-log-1011',
            'name'                 => 'log-node',
            'status'               => ProvisionedResource::STATUS_CREATING,
        ]);

        RunLog::create([
            'provisioned_resource_id' => $resource->id,
            'source'                  => RunLog::SOURCE_RESOURCE,
            'level'                   => RunLog::LEVEL_INFO,
            'message'                 => 'Resource provisioning started',
        ]);
        RunLog::create([
            'provisioned_resource_id' => $resource->id,
            'source'                  => RunLog::SOURCE_RESOURCE,
            'level'                   => RunLog::LEVEL_ERROR,
            'message'                 => 'Provisioning failed with error',
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/resources/{$resource->id}/logs");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_resource_logs_returns_only_logs_for_that_resource(): void
    {
        $user        = User::factory()->create();
        $deployment  = $this->createDeploymentForUser($user);

        $resource1 = ProvisionedResource::create([
            'deployment_id'        => $deployment->id,
            'provider_resource_id' => 'res-A',
            'status'               => ProvisionedResource::STATUS_RUNNING,
        ]);
        $resource2 = ProvisionedResource::create([
            'deployment_id'        => $deployment->id,
            'provider_resource_id' => 'res-B',
            'status'               => ProvisionedResource::STATUS_RUNNING,
        ]);

        RunLog::create(['provisioned_resource_id' => $resource1->id, 'source' => RunLog::SOURCE_RESOURCE, 'level' => RunLog::LEVEL_INFO, 'message' => 'log for resource 1']);
        RunLog::create(['provisioned_resource_id' => $resource2->id, 'source' => RunLog::SOURCE_RESOURCE, 'level' => RunLog::LEVEL_INFO, 'message' => 'log for resource 2']);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/resources/{$resource1->id}/logs");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'log for resource 1');
    }

    public function test_resource_logs_after_id_returns_only_newer_entries(): void
    {
        $user       = User::factory()->create();
        $deployment = $this->createDeploymentForUser($user);

        $resource = ProvisionedResource::create([
            'deployment_id'        => $deployment->id,
            'provider_resource_id' => 'res-incr',
            'status'               => ProvisionedResource::STATUS_RUNNING,
        ]);

        $log1 = RunLog::create(['provisioned_resource_id' => $resource->id, 'source' => RunLog::SOURCE_RESOURCE, 'level' => RunLog::LEVEL_INFO, 'message' => 'first']);
        RunLog::create(['provisioned_resource_id' => $resource->id, 'source' => RunLog::SOURCE_RESOURCE, 'level' => RunLog::LEVEL_INFO, 'message' => 'second']);
        RunLog::create(['provisioned_resource_id' => $resource->id, 'source' => RunLog::SOURCE_RESOURCE, 'level' => RunLog::LEVEL_INFO, 'message' => 'third']);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/resources/{$resource->id}/logs?after_id={$log1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
