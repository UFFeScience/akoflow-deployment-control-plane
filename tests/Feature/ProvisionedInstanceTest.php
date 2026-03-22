<?php

namespace Tests\Feature;

use App\Models\Deployment;
use App\Models\DeploymentTemplate;
use App\Models\Environment;
use App\Models\EnvironmentTemplate;
use App\Models\EnvironmentTemplateVersion;
use App\Models\InstanceLog;
use App\Models\InstanceType;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Provider;
use App\Models\ProvisionedInstance;
use App\Models\InstanceGroup;
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

    private function createDeploymentAndInstanceType(User $user): array
    {
        $provider = Provider::create([
            'name'   => 'Instance Provider',
            'type'   => Provider::TYPES[0],
            'status' => Provider::STATUSES[0],
        ]);

        $template = EnvironmentTemplate::create([
            'name'         => 'Instance Template',
            'slug'         => 'instance-template-' . uniqid(),
            'description'  => 'Template for instances',
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

        $org        = Organization::factory()->create(['user_id' => $user->id]);
        $project    = Project::factory()->create(['organization_id' => $org->id]);
        $environment = Environment::create([
            'project_id' => $project->id,
            'name'       => 'Instance Environment',
            'status'     => Environment::STATUSES[0],
        ]);

        $deployment = Deployment::create([
            'environment_id'    => $environment->id,
            'deployment_template_id' => $deploymentTemplate->id,
            'provider_id'      => $provider->id,
            'region'           => 'us-central1',
            'environment_type' => Deployment::ENVIRONMENT_TYPES[0],
            'name'             => 'Deployment for instances',
            'status'           => Deployment::STATUSES[1],
        ]);

        $instanceType = InstanceType::create([
            'provider_id' => $provider->id,
            'name'        => 'n2-standard-4',
            'vcpus'       => 4,
            'memory_mb'   => 16384,
            'status'      => InstanceType::STATUSES[0],
            'is_active'   => true,
        ]);

        $group = InstanceGroup::create([
            'deployment_id'       => $deployment->id,
            'instance_type_id' => $instanceType->id,
            'role'             => 'master',
            'quantity'         => 2,
        ]);

        return compact('deployment', 'instanceType', 'group');
    }

    public function test_user_can_list_instances_by_deployment(): void
    {
        $user = User::factory()->create();
        ['deployment' => $deployment, 'instanceType' => $instanceType, 'group' => $group] = $this->createDeploymentAndInstanceType($user);

        ProvisionedInstance::create([
            'deployment_id' => $deployment->id,
            'instance_type_id' => $instanceType->id,
            'provider_instance_id' => 'i-123',
            'role' => ProvisionedInstance::ROLES[0],
            'status' => ProvisionedInstance::STATUSES[1],
            'health_status' => ProvisionedInstance::HEALTH_STATUSES[0],
            'instance_group_id' => $group->id,
        ]);
        ProvisionedInstance::create([
            'deployment_id' => $deployment->id,
            'instance_type_id' => $instanceType->id,
            'provider_instance_id' => 'i-456',
            'role' => ProvisionedInstance::ROLES[1],
            'status' => ProvisionedInstance::STATUSES[1],
            'health_status' => ProvisionedInstance::HEALTH_STATUSES[0],
            'instance_group_id' => $group->id,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/deployments/{$deployment->id}/instances");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_get_instance_details(): void
    {
        $user = User::factory()->create();
        ['deployment' => $deployment, 'instanceType' => $instanceType] = $this->createDeploymentAndInstanceType($user);

        $instance = ProvisionedInstance::create([
            'deployment_id' => $deployment->id,
            'instance_type_id' => $instanceType->id,
            'provider_instance_id' => 'i-789',
            'role' => ProvisionedInstance::ROLES[2],
            'status' => ProvisionedInstance::STATUSES[1],
            'health_status' => ProvisionedInstance::HEALTH_STATUSES[0],
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/instances/{$instance->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $instance->id)
            ->assertJsonPath('data.deployment_id', $deployment->id);
    }

    public function test_user_can_list_instance_logs(): void
    {
        $user = User::factory()->create();
        ['deployment' => $deployment, 'instanceType' => $instanceType] = $this->createDeploymentAndInstanceType($user);

        $instance = ProvisionedInstance::create([
            'deployment_id' => $deployment->id,
            'instance_type_id' => $instanceType->id,
            'provider_instance_id' => 'i-1011',
            'role' => ProvisionedInstance::ROLES[0],
            'status' => ProvisionedInstance::STATUSES[1],
            'health_status' => ProvisionedInstance::HEALTH_STATUSES[0],
        ]);

        InstanceLog::create([
            'provisioned_instance_id' => $instance->id,
            'level' => InstanceLog::LEVELS[0],
            'message' => 'Instance started',
        ]);
        InstanceLog::create([
            'provisioned_instance_id' => $instance->id,
            'level' => InstanceLog::LEVELS[2],
            'message' => 'Error detected',
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/instances/{$instance->id}/logs");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
