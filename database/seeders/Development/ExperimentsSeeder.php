<?php

namespace Database\Seeders\Development;

use App\Models\Cluster;
use App\Models\ClusterTemplate;
use App\Models\Experiment;
use App\Models\InstanceGroup;
use App\Models\InstanceType;
use App\Models\Project;
use App\Models\Provider;
use App\Models\ProvisionedInstance;
use Illuminate\Database\Seeder;

class ExperimentsSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::where('name', 'Projeto Demo')->first();
        $clusterTemplate = ClusterTemplate::first();
        $provider = Provider::where('name', 'AWS')->first();
        $instanceType = InstanceType::where('name', 'p3.2xlarge')->first();

        if (! $project || ! $clusterTemplate || ! $provider || ! $instanceType) {
            return;
        }

        $experiment = Experiment::firstOrCreate(
            [
                'project_id' => $project->id,
                'name' => 'Experiment Demo',
            ],
            [
                'status' => 'RUNNING',
            ]
        );

        $cluster = Cluster::firstOrCreate(
            [
                'experiment_id' => $experiment->id,
                'name' => 'demo-cluster',
            ],
            [
                'cluster_template_id' => $clusterTemplate->id,
                'provider_id' => $provider->id,
                'region' => 'us-east-1',
                'environment_type' => 'CLOUD',
                'status' => 'RUNNING',
            ]
        );

        $masterGroup = InstanceGroup::firstOrCreate(
            [
                'cluster_id' => $cluster->id,
                'instance_type_id' => $instanceType->id,
                'role' => 'master',
            ],
            [
                'quantity' => 1,
                'metadata_json' => ['tier' => 'control-plane'],
            ]
        );

        $workerGroup = InstanceGroup::firstOrCreate(
            [
                'cluster_id' => $cluster->id,
                'instance_type_id' => $instanceType->id,
                'role' => 'worker',
            ],
            [
                'quantity' => 1,
                'metadata_json' => ['tier' => 'compute'],
            ]
        );

        ProvisionedInstance::firstOrCreate(
            [
                'cluster_id' => $cluster->id,
                'instance_type_id' => $instanceType->id,
                'role' => 'master',
                'instance_group_id' => $masterGroup->id,
            ],
            [
                'status' => 'RUNNING',
                'health_status' => 'HEALTHY',
                'public_ip' => '54.0.0.10',
                'private_ip' => '10.0.0.10',
            ]
        );

        ProvisionedInstance::firstOrCreate(
            [
                'cluster_id' => $cluster->id,
                'instance_type_id' => $instanceType->id,
                'role' => 'worker',
                'provider_instance_id' => 'i-demo-worker-1',
                'instance_group_id' => $workerGroup->id,
            ],
            [
                'status' => 'RUNNING',
                'health_status' => 'HEALTHY',
                'public_ip' => '54.0.0.11',
                'private_ip' => '10.0.0.11',
            ]
        );
    }
}
