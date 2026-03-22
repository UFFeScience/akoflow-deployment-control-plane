<?php

namespace Tests\Feature;

use App\Models\Deployment;
use App\Models\ClusterTemplate;
use App\Models\Environment;
use App\Models\EnvironmentTemplate;
use App\Models\EnvironmentTemplateVersion;
use App\Models\InstanceGroup;
use App\Models\InstanceType;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Provider;
use App\Models\ProvisionedInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClusterTest extends TestCase
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

        $clusterTemplate = ClusterTemplate::create([
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

        $org        = Organization::factory()->create(['user_id' => $user->id]);
        $project    = Project::factory()->create(['organization_id' => $org->id]);
        $environment = Environment::create([
            'project_id' => $project->id,
            'name'       => 'Environment for deployment',
            'status'     => Environment::STATUSES[0],
        ]);

        return compact('provider', 'clusterTemplate', 'environment', 'instanceType');
    }

    public function test_user_can_list_clusters_by_environment(): void
    {
        $user = User::factory()->create();
        ['provider' => $provider, 'clusterTemplate' => $clusterTemplate, 'environment' => $environment] = $this->createDependencies($user);

        Deployment::create([
            'environment_id' => $environment->id,
            'cluster_template_id' => $clusterTemplate->id,
            'provider_id' => $provider->id,
            'region' => 'us-east-1',
            'environment_type' => Deployment::ENVIRONMENT_TYPES[0],
            'name' => 'Primary Deployment',
            'status' => Deployment::STATUSES[1],
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/environments/{$environment->id}/deployments");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_create_cluster(): void
    {
        $user = User::factory()->create();
        ['provider' => $provider, 'clusterTemplate' => $clusterTemplate, 'environment' => $environment] = $this->createDependencies($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/environments/{$environment->id}/deployments", [
                'cluster_template_id' => $clusterTemplate->id,
                'provider_id' => $provider->id,
                'region' => 'us-west-2',
                'environment_type' => Deployment::ENVIRONMENT_TYPES[0],
                'name' => 'Created Deployment',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Created Deployment')
            ->assertJsonPath('data.environment_id', (string) $environment->id);

        $this->assertDatabaseHas('deployments', [
            'environment_id' => $environment->id,
            'name' => 'Created Deployment',
        ]);
    }

    public function test_user_can_create_cluster_with_instance_groups_and_metadata(): void
    {
        $user = User::factory()->create();
        ['provider' => $provider, 'clusterTemplate' => $clusterTemplate, 'environment' => $environment, 'instanceType' => $instanceType] = $this->createDependencies($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/environments/{$environment->id}/deployments", [
                'cluster_template_id' => $clusterTemplate->id,
                'provider_id' => $provider->id,
                'region' => 'us-east-1',
                'environment_type' => Deployment::ENVIRONMENT_TYPES[0],
                'name' => 'Deployment with groups',
                'instance_groups' => [
                    [
                        'instance_type_id' => $instanceType->id,
                        'role' => 'master',
                        'quantity' => 1,
                        'metadata' => ['tier' => 'control'],
                    ],
                    [
                        'instance_type_id' => $instanceType->id,
                        'role' => 'worker',
                        'quantity' => 2,
                        'metadata' => ['tier' => 'compute'],
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonCount(2, 'data.instance_groups');

        $clusterId = $response->json('data.id');
        $deployment = Deployment::with('instanceGroups')->find($clusterId);

        $this->assertNotNull($deployment);
        $this->assertCount(2, $deployment->instanceGroups);

        $master = $deployment->instanceGroups->firstWhere('role', 'master');
        $worker = $deployment->instanceGroups->firstWhere('role', 'worker');

        $this->assertEquals(['tier' => 'control'], $master->metadata_json);
        $this->assertEquals(['tier' => 'compute'], $worker->metadata_json);
        $this->assertEquals(1, $master->quantity);
        $this->assertEquals(2, $worker->quantity);

        $this->assertDatabaseCount('provisioned_instances', 3);
        $this->assertDatabaseHas('instance_groups', [
            'cluster_id' => $clusterId,
            'role' => 'master',
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('instance_groups', [
            'cluster_id' => $clusterId,
            'role' => 'worker',
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('provisioned_instances', [
            'cluster_id' => $clusterId,
            'instance_group_id' => $master->id,
        ]);
        $this->assertDatabaseHas('provisioned_instances', [
            'cluster_id' => $clusterId,
            'instance_group_id' => $worker->id,
        ]);
    }

    public function test_user_can_update_cluster_nodes_per_group(): void
    {
        $user = User::factory()->create();
        ['provider' => $provider, 'clusterTemplate' => $clusterTemplate, 'environment' => $environment, 'instanceType' => $instanceType] = $this->createDependencies($user);

        $deployment = Deployment::create([
            'environment_id' => $environment->id,
            'cluster_template_id' => $clusterTemplate->id,
            'provider_id' => $provider->id,
            'region' => 'us-east-1',
            'environment_type' => Deployment::ENVIRONMENT_TYPES[0],
            'name' => 'Scalable Deployment',
            'status' => Deployment::STATUSES[1],
        ]);

        $masterGroup = InstanceGroup::create([
            'cluster_id' => $deployment->id,
            'instance_type_id' => $instanceType->id,
            'role' => 'master',
            'quantity' => 2,
        ]);
        $workerGroup = InstanceGroup::create([
            'cluster_id' => $deployment->id,
            'instance_type_id' => $instanceType->id,
            'role' => 'worker',
            'quantity' => 1,
        ]);

        ProvisionedInstance::create([
            'cluster_id' => $deployment->id,
            'instance_group_id' => $masterGroup->id,
            'instance_type_id' => $instanceType->id,
            'role' => 'master',
            'status' => ProvisionedInstance::STATUS_RUNNING,
        ]);
        ProvisionedInstance::create([
            'cluster_id' => $deployment->id,
            'instance_group_id' => $masterGroup->id,
            'instance_type_id' => $instanceType->id,
            'role' => 'master',
            'status' => ProvisionedInstance::STATUS_RUNNING,
        ]);
        ProvisionedInstance::create([
            'cluster_id' => $deployment->id,
            'instance_group_id' => $workerGroup->id,
            'instance_type_id' => $instanceType->id,
            'role' => 'worker',
            'status' => ProvisionedInstance::STATUS_RUNNING,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->patchJson("/api/deployments/{$deployment->id}/nodes", [
                'instance_groups' => [
                    ['id' => $masterGroup->id, 'quantity' => 1],
                    ['id' => $workerGroup->id, 'quantity' => 2],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.instance_groups');

        $this->assertDatabaseHas('instance_groups', [
            'id' => $masterGroup->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('instance_groups', [
            'id' => $workerGroup->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('provisioned_instances', [
            'instance_group_id' => $masterGroup->id,
            'status' => ProvisionedInstance::STATUS_REMOVING,
        ]);

        $this->assertDatabaseCount('provisioned_instances', 4);
    }

    public function test_user_can_scale_cluster(): void
    {
        $user = User::factory()->create();
        ['provider' => $provider, 'clusterTemplate' => $clusterTemplate, 'environment' => $environment] = $this->createDependencies($user);

        $deployment = Deployment::create([
            'environment_id' => $environment->id,
            'cluster_template_id' => $clusterTemplate->id,
            'provider_id' => $provider->id,
            'region' => 'eu-west-1',
            'environment_type' => Deployment::ENVIRONMENT_TYPES[1],
            'name' => 'Scale Deployment',
            'status' => Deployment::STATUSES[0],
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/deployments/{$deployment->id}/scale", [
                'action' => 'SCALE_UP',
                'old_value' => 2,
                'new_value' => 4,
                'triggered_by' => 'USER',
            ]);

        $response->assertStatus(202)
            ->assertJson(['message' => 'Scale event recorded']);

        $this->assertDatabaseHas('cluster_scaling_events', [
            'cluster_id' => $deployment->id,
            'action' => 'SCALE_UP',
            'old_value' => 2,
            'new_value' => 4,
            'triggered_by' => 'USER',
        ]);
    }

    public function test_user_can_delete_cluster(): void
    {
        $user = User::factory()->create();
        ['provider' => $provider, 'clusterTemplate' => $clusterTemplate, 'environment' => $environment] = $this->createDependencies($user);

        $deployment = Deployment::create([
            'environment_id' => $environment->id,
            'cluster_template_id' => $clusterTemplate->id,
            'provider_id' => $provider->id,
            'region' => 'ap-southeast-1',
            'environment_type' => Deployment::ENVIRONMENT_TYPES[2],
            'name' => 'Disposable Deployment',
            'status' => Deployment::STATUSES[2],
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->deleteJson("/api/deployments/{$deployment->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('deployments', [
            'id' => $deployment->id,
        ]);
    }
}
