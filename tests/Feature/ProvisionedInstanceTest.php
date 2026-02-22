<?php

namespace Tests\Feature;

use App\Models\Cluster;
use App\Models\ClusterTemplate;
use App\Models\Experiment;
use App\Models\ExperimentTemplate;
use App\Models\ExperimentTemplateVersion;
use App\Models\InstanceLog;
use App\Models\InstanceType;
use App\Models\Project;
use App\Models\Provider;
use App\Models\ProvisionedInstance;
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

    private function createClusterAndInstanceType(): array
    {
        $provider = Provider::create([
            'name' => 'Instance Provider',
            'type' => Provider::TYPES[0],
            'status' => Provider::STATUSES[0],
        ]);

        $template = ExperimentTemplate::create([
            'name' => 'Instance Template',
            'slug' => 'instance-template-' . uniqid(),
            'runtime_type' => ExperimentTemplate::RUNTIME_TYPES[0],
            'description' => 'Template for instances',
            'is_public' => true,
        ]);

        $version = ExperimentTemplateVersion::create([
            'template_id' => $template->id,
            'version' => 'v1',
            'definition_json' => ['steps' => []],
            'is_active' => true,
        ]);

        $clusterTemplate = ClusterTemplate::create([
            'template_version_id' => $version->id,
            'custom_parameters_json' => ['nodes' => 2],
        ]);

        $project = Project::factory()->create();
        $experiment = Experiment::create([
            'project_id' => $project->id,
            'name' => 'Instance Experiment',
            'status' => Experiment::STATUSES[0],
        ]);

        $cluster = Cluster::create([
            'experiment_id' => $experiment->id,
            'cluster_template_id' => $clusterTemplate->id,
            'provider_id' => $provider->id,
            'region' => 'us-central1',
            'environment_type' => Cluster::ENVIRONMENT_TYPES[0],
            'name' => 'Cluster for instances',
            'status' => Cluster::STATUSES[1],
        ]);

        $instanceType = InstanceType::create([
            'provider_id' => $provider->id,
            'name' => 'n2-standard-4',
            'vcpus' => 4,
            'memory_mb' => 16384,
            'status' => InstanceType::STATUSES[0],
            'is_active' => true,
        ]);

        return compact('cluster', 'instanceType');
    }

    public function test_user_can_list_instances_by_cluster(): void
    {
        $user = User::factory()->create();
        ['cluster' => $cluster, 'instanceType' => $instanceType] = $this->createClusterAndInstanceType();

        ProvisionedInstance::create([
            'cluster_id' => $cluster->id,
            'instance_type_id' => $instanceType->id,
            'provider_instance_id' => 'i-123',
            'role' => ProvisionedInstance::ROLES[0],
            'status' => ProvisionedInstance::STATUSES[1],
            'health_status' => ProvisionedInstance::HEALTH_STATUSES[0],
        ]);
        ProvisionedInstance::create([
            'cluster_id' => $cluster->id,
            'instance_type_id' => $instanceType->id,
            'provider_instance_id' => 'i-456',
            'role' => ProvisionedInstance::ROLES[1],
            'status' => ProvisionedInstance::STATUSES[1],
            'health_status' => ProvisionedInstance::HEALTH_STATUSES[0],
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/clusters/{$cluster->id}/instances");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_get_instance_details(): void
    {
        $user = User::factory()->create();
        ['cluster' => $cluster, 'instanceType' => $instanceType] = $this->createClusterAndInstanceType();

        $instance = ProvisionedInstance::create([
            'cluster_id' => $cluster->id,
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
            ->assertJsonPath('data.cluster_id', $cluster->id);
    }

    public function test_user_can_list_instance_logs(): void
    {
        $user = User::factory()->create();
        ['cluster' => $cluster, 'instanceType' => $instanceType] = $this->createClusterAndInstanceType();

        $instance = ProvisionedInstance::create([
            'cluster_id' => $cluster->id,
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
