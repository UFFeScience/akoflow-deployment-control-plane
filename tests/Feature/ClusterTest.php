<?php

namespace Tests\Feature;

use App\Models\Cluster;
use App\Models\ClusterTemplate;
use App\Models\Experiment;
use App\Models\ExperimentTemplate;
use App\Models\ExperimentTemplateVersion;
use App\Models\Project;
use App\Models\Provider;
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

    private function createDependencies(): array
    {
        $provider = Provider::create([
            'name' => 'Provider for Cluster',
            'type' => Provider::TYPES[0],
            'status' => Provider::STATUSES[0],
        ]);

        $template = ExperimentTemplate::create([
            'name' => 'Cluster Template',
            'slug' => 'cluster-template-' . uniqid(),
            'runtime_type' => ExperimentTemplate::RUNTIME_TYPES[0],
            'description' => 'Cluster template description',
            'is_public' => true,
        ]);

        $version = ExperimentTemplateVersion::create([
            'template_id' => $template->id,
            'version' => 'v1',
            'definition_json' => ['nodes' => []],
            'is_active' => true,
        ]);

        $clusterTemplate = ClusterTemplate::create([
            'template_version_id' => $version->id,
            'custom_parameters_json' => ['size' => 3],
        ]);

        $project = Project::factory()->create();
        $experiment = Experiment::create([
            'project_id' => $project->id,
            'name' => 'Experiment for cluster',
            'status' => Experiment::STATUSES[0],
        ]);

        return compact('provider', 'clusterTemplate', 'experiment');
    }

    public function test_user_can_list_clusters_by_experiment(): void
    {
        $user = User::factory()->create();
        ['provider' => $provider, 'clusterTemplate' => $clusterTemplate, 'experiment' => $experiment] = $this->createDependencies();

        Cluster::create([
            'experiment_id' => $experiment->id,
            'cluster_template_id' => $clusterTemplate->id,
            'provider_id' => $provider->id,
            'region' => 'us-east-1',
            'environment_type' => Cluster::ENVIRONMENT_TYPES[0],
            'name' => 'Primary Cluster',
            'status' => Cluster::STATUSES[1],
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/experiments/{$experiment->id}/clusters");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_create_cluster(): void
    {
        $user = User::factory()->create();
        ['provider' => $provider, 'clusterTemplate' => $clusterTemplate, 'experiment' => $experiment] = $this->createDependencies();

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/experiments/{$experiment->id}/clusters", [
                'cluster_template_id' => $clusterTemplate->id,
                'provider_id' => $provider->id,
                'region' => 'us-west-2',
                'environment_type' => Cluster::ENVIRONMENT_TYPES[0],
                'name' => 'Created Cluster',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Created Cluster')
            ->assertJsonPath('data.experiment_id', (string) $experiment->id);

        $this->assertDatabaseHas('clusters', [
            'experiment_id' => $experiment->id,
            'name' => 'Created Cluster',
        ]);
    }

    public function test_user_can_scale_cluster(): void
    {
        $user = User::factory()->create();
        ['provider' => $provider, 'clusterTemplate' => $clusterTemplate, 'experiment' => $experiment] = $this->createDependencies();

        $cluster = Cluster::create([
            'experiment_id' => $experiment->id,
            'cluster_template_id' => $clusterTemplate->id,
            'provider_id' => $provider->id,
            'region' => 'eu-west-1',
            'environment_type' => Cluster::ENVIRONMENT_TYPES[1],
            'name' => 'Scale Cluster',
            'status' => Cluster::STATUSES[0],
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/clusters/{$cluster->id}/scale", [
                'action' => 'SCALE_UP',
                'old_value' => 2,
                'new_value' => 4,
                'triggered_by' => 'USER',
            ]);

        $response->assertStatus(202)
            ->assertJson(['message' => 'Scale event recorded']);

        $this->assertDatabaseHas('cluster_scaling_events', [
            'cluster_id' => $cluster->id,
            'action' => 'SCALE_UP',
            'old_value' => 2,
            'new_value' => 4,
            'triggered_by' => 'USER',
        ]);
    }

    public function test_user_can_delete_cluster(): void
    {
        $user = User::factory()->create();
        ['provider' => $provider, 'clusterTemplate' => $clusterTemplate, 'experiment' => $experiment] = $this->createDependencies();

        $cluster = Cluster::create([
            'experiment_id' => $experiment->id,
            'cluster_template_id' => $clusterTemplate->id,
            'provider_id' => $provider->id,
            'region' => 'ap-southeast-1',
            'environment_type' => Cluster::ENVIRONMENT_TYPES[2],
            'name' => 'Disposable Cluster',
            'status' => Cluster::STATUSES[2],
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->deleteJson("/api/clusters/{$cluster->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('clusters', [
            'id' => $cluster->id,
        ]);
    }
}
