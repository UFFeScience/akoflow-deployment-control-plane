<?php

namespace Database\Seeders\Development;

use App\Models\Organization;
use App\Models\ProviderVariableSchema;
use Illuminate\Database\Seeder;

class ProviderSchemasSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('name', 'AkoCloud Demo')->first();
        if (! $organization) {
            return;
        }

        // Schemas indexed by provider slug so we can look up the provider_id
        $schemasBySlug = [
            // ─── GCP ──────────────────────────────────────────────────────────
            'gcp' => [
                [
                    'section'       => 'authentication',
                    'name'          => 'service_account_json',
                    'label'         => 'Service Account JSON',
                    'description'   => 'Full JSON key file for the GCP service account with the required permissions.',
                    'type'          => 'textarea',
                    'required'      => true,
                    'is_sensitive'  => true,
                    'position'      => 1,
                    'options_json'  => null,
                    'default_value' => null,
                ],
                [
                    'section'       => 'general',
                    'name'          => 'gcp_project_id',
                    'label'         => 'Project ID',
                    'description'   => 'The unique GCP project identifier (e.g. my-project-123456).',
                    'type'          => 'string',
                    'required'      => true,
                    'is_sensitive'  => false,
                    'position'      => 2,
                    'options_json'  => null,
                    'default_value' => null,
                ],
                [
                    'section'       => 'general',
                    'name'          => 'gcp_region',
                    'label'         => 'Default Region',
                    'description'   => 'GCP region where resources will be provisioned by default.',
                    'type'          => 'select',
                    'required'      => false,
                    'is_sensitive'  => false,
                    'position'      => 3,
                    'options_json'  => json_encode([
                        'us-central1', 'us-west1', 'us-west2', 'us-east1', 'us-east4',
                        'europe-west1', 'europe-west2', 'europe-west4',
                        'asia-southeast1', 'asia-east1', 'asia-northeast1',
                    ]),
                    'default_value' => 'us-central1',
                ],
            ],

            // ─── AWS ──────────────────────────────────────────────────────────
            'aws' => [
                [
                    'section'       => 'authentication',
                    'name'          => 'aws_access_key_id',
                    'label'         => 'Access Key ID',
                    'description'   => 'AWS IAM access key ID.',
                    'type'          => 'secret',
                    'required'      => true,
                    'is_sensitive'  => true,
                    'position'      => 1,
                    'options_json'  => null,
                    'default_value' => null,
                ],
                [
                    'section'       => 'authentication',
                    'name'          => 'aws_secret_access_key',
                    'label'         => 'Secret Access Key',
                    'description'   => 'AWS IAM secret access key paired with the Access Key ID.',
                    'type'          => 'secret',
                    'required'      => true,
                    'is_sensitive'  => true,
                    'position'      => 2,
                    'options_json'  => null,
                    'default_value' => null,
                ],
                [
                    'section'       => 'general',
                    'name'          => 'aws_region',
                    'label'         => 'Default Region',
                    'description'   => 'AWS region where resources will be provisioned by default.',
                    'type'          => 'select',
                    'required'      => true,
                    'is_sensitive'  => false,
                    'position'      => 3,
                    'options_json'  => json_encode([
                        'us-east-1', 'us-east-2', 'us-west-1', 'us-west-2',
                        'eu-west-1', 'eu-west-2', 'eu-central-1',
                        'ap-southeast-1', 'ap-northeast-1', 'ap-south-1',
                        'sa-east-1', 'ca-central-1',
                    ]),
                    'default_value' => 'us-east-1',
                ],
                [
                    'section'       => 'general',
                    'name'          => 'aws_account_id',
                    'label'         => 'Account ID',
                    'description'   => '12-digit AWS account identifier.',
                    'type'          => 'string',
                    'required'      => false,
                    'is_sensitive'  => false,
                    'position'      => 4,
                    'options_json'  => null,
                    'default_value' => null,
                ],
            ],

            // ─── Slurm / HPC ──────────────────────────────────────────────────
            'slurm' => [
                [
                    'section'       => 'connection',
                    'name'          => 'slurm_host',
                    'label'         => 'Host / IP',
                    'description'   => 'Hostname or IP address of the Slurm login/head node.',
                    'type'          => 'string',
                    'required'      => true,
                    'is_sensitive'  => false,
                    'position'      => 1,
                    'options_json'  => null,
                    'default_value' => null,
                ],
                [
                    'section'       => 'connection',
                    'name'          => 'slurm_username',
                    'label'         => 'SSH Username',
                    'description'   => 'Username used to connect via SSH to the head node.',
                    'type'          => 'string',
                    'required'      => true,
                    'is_sensitive'  => false,
                    'position'      => 2,
                    'options_json'  => null,
                    'default_value' => null,
                ],
                [
                    'section'       => 'connection',
                    'name'          => 'slurm_ssh_private_key',
                    'label'         => 'SSH Private Key',
                    'description'   => 'PEM/RSA private key used for SSH authentication to the cluster.',
                    'type'          => 'textarea',
                    'required'      => true,
                    'is_sensitive'  => true,
                    'position'      => 3,
                    'options_json'  => null,
                    'default_value' => null,
                ],
                [
                    'section'       => 'scheduler',
                    'name'          => 'slurm_partition',
                    'label'         => 'Default Partition',
                    'description'   => 'Slurm partition (queue) to submit jobs to by default.',
                    'type'          => 'string',
                    'required'      => false,
                    'is_sensitive'  => false,
                    'position'      => 4,
                    'options_json'  => null,
                    'default_value' => null,
                ],
                [
                    'section'       => 'scheduler',
                    'name'          => 'slurm_account',
                    'label'         => 'Slurm Account',
                    'description'   => 'Slurm account/project used for job accounting.',
                    'type'          => 'string',
                    'required'      => false,
                    'is_sensitive'  => false,
                    'position'      => 5,
                    'options_json'  => null,
                    'default_value' => null,
                ],
                [
                    'section'       => 'scheduler',
                    'name'          => 'slurm_max_nodes',
                    'label'         => 'Max Nodes',
                    'description'   => 'Maximum number of nodes to request per job.',
                    'type'          => 'number',
                    'required'      => false,
                    'is_sensitive'  => false,
                    'position'      => 6,
                    'options_json'  => null,
                    'default_value' => null,
                ],
                [
                    'section'       => 'scheduler',
                    'name'          => 'slurm_default_time_limit',
                    'label'         => 'Default Time Limit',
                    'description'   => 'Default wall-clock time limit for jobs (e.g. 02:00:00 for 2 hours).',
                    'type'          => 'string',
                    'required'      => false,
                    'is_sensitive'  => false,
                    'position'      => 7,
                    'options_json'  => null,
                    'default_value' => '01:00:00',
                ],
            ],
        ];

        foreach ($schemasBySlug as $slug => $schemas) {
            foreach ($schemas as $schema) {
                ProviderVariableSchema::updateOrCreate(
                    ['provider_slug' => $slug, 'name' => $schema['name']],
                    array_merge($schema, ['provider_slug' => $slug])
                );
            }
        }
    }
}

