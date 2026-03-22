<?php

namespace Database\Seeders\Development;

use App\Models\EnvironmentTemplateTerraformModule;
use Illuminate\Database\Seeder;

class TemplateTerraformModulesSeeder extends Seeder
{
    public function run(): void
    {
        $modules = $this->modules();

        foreach ($modules as $module) {
            EnvironmentTemplateTerraformModule::query()->updateOrCreate(
                [
                    'template_version_id' => $module['template_version_id'],
                    'provider_type' => $module['provider_type'],
                ],
                [
                    'module_slug'          => $module['module_slug'],
                    'main_tf'              => $module['main_tf'],
                    'variables_tf'         => $module['variables_tf'],
                    'outputs_tf'           => $module['outputs_tf'],
                    'tfvars_mapping_json'  => $module['tfvars_mapping_json'],
                    'outputs_mapping_json' => $module['outputs_mapping_json'],
                    'credential_env_keys'  => $module['credential_env_keys'],
                ],
            );
        }
    }

    private function modules(): array
    {
        return [
            $this->awsMicroNginx(),
            $this->gcpMicroNginx(),
            $this->awsEksAkoflow(),
            $this->gcpGkeAkoflow(),
        ];
    }

    private function awsMicroNginx(): array
    {
        return [
            'template_version_id' => 1,
            'provider_type' => 'aws',
            'module_slug' => 'micro-nginx-aws',
            'main_tf' => $this->readTerraformFile('hello_aws/main.tf'),
            'variables_tf' => $this->readTerraformFile('hello_aws/variables.tf'),
            'outputs_tf' => $this->readTerraformFile('hello_aws/outputs.tf'),
            'tfvars_mapping_json' => [
                'environment_configuration' => [
                    'region'        => 'region',
                    'zone'          => 'zone',
                    'instance_type' => 'instance_type',
                    'nginx_port'    => 'nginx_port',
                ],
                'instance_configurations' => [
                    'single-vm' => [],
                ],
            ],
            'credential_env_keys' => [
                'AKO_AWS_ACCESS_KEY_ID',
                'AKO_AWS_SECRET_ACCESS_KEY',
            ],
            'outputs_mapping_json' => [
                'resources' => [
                    [
                        'name'          => 'nginx-vm',
                        'terraform_type'=> 'aws_instance',
                        'outputs'       => [
                            'provider_resource_id' => 'instance_id',
                            'public_ip'            => 'public_ip',
                            'private_ip'           => 'private_ip',
                            'iframe_url'           => 'akoflow_iframe_url',
                            'metadata'             => [
                                'nginx_url'         => 'nginx_url',
                                'security_group_id' => 'security_group_id',
                                'resolved_ami'      => 'resolved_ami',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function gcpMicroNginx(): array
    {
        return [
            'template_version_id' => 1,
            'provider_type' => 'gcp',
            'module_slug' => 'micro-nginx-gcp',
            'main_tf' => $this->readTerraformFile('hello_gcp/main.tf'),
            'variables_tf' => $this->readTerraformFile('hello_gcp/variables.tf'),
            'outputs_tf' => $this->readTerraformFile('hello_gcp/outputs.tf'),
            'tfvars_mapping_json' => [
                'environment_configuration' => [
                    'region'       => 'region',
                    'zone'         => 'zone',
                    'project_id'   => 'project_id',
                    'machine_type' => 'machine_type',
                    'nginx_port'   => 'nginx_port',
                ],
                'instance_configurations' => [
                    'single-vm' => [],
                ],
            ],
            'credential_env_keys' => [
                'AKO_GCP_SERVICE_ACCOUNT_KEY',
            ],
            'outputs_mapping_json' => [
                'resources' => [
                    [
                        'name'          => 'nginx-vm',
                        'terraform_type'=> 'google_compute_instance',
                        'outputs'       => [
                            'provider_resource_id' => 'instance_id',
                            'public_ip'            => 'public_ip',
                            'private_ip'           => 'private_ip',
                            'iframe_url'           => 'akoflow_iframe_url',
                            'metadata'             => [
                                'nginx_url'      => 'nginx_url',
                                'firewall_name'  => 'firewall_name',
                                'resolved_image' => 'resolved_image',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function readTerraformFile(string $relativePath): string
    {
        $basePath = database_path('seeders/Development/TemplateDefinitions/terraform/modules');
        $fullPath = $basePath . DIRECTORY_SEPARATOR . $relativePath;

        return (string) file_get_contents($fullPath);
    }

    private function awsEksAkoflow(): array
    {
        return [
            'template_version_id' => 2,
            'provider_type'       => 'aws',
            'module_slug'         => 'akoflow-engine-eks-aws',
            'main_tf'             => $this->readTerraformFile('aws_eks_akoflow/main.tf'),
            'variables_tf'        => $this->readTerraformFile('aws_eks_akoflow/variables.tf'),
            'outputs_tf'          => $this->readTerraformFile('aws_eks_akoflow/outputs.tf'),
            'tfvars_mapping_json' => [
                'environment_configuration' => [
                    'region'               => 'region',
                    'eks_cluster_version'  => 'eks_cluster_version',
                    'node_instance_type'   => 'node_instance_type',
                    'node_count'           => 'node_count',
                    'node_min_count'       => 'node_min_count',
                    'node_max_count'       => 'node_max_count',
                    'engine_instance_type' => 'engine_instance_type',
                    'akoflow_api_port'     => 'akoflow_api_port',
                    'akoflow_allowed_ips'  => 'akoflow_allowed_ips',
                ],
                'instance_configurations' => [
                    'k8s-cluster' => [],
                    'engine-vm'   => [],
                ],
            ],
            'credential_env_keys' => [
                'AKO_AWS_ACCESS_KEY_ID',
                'AKO_AWS_SECRET_ACCESS_KEY',
            ],
            'outputs_mapping_json' => [
                'resources' => [
                    [
                        'name'           => 'eks-cluster',
                        'terraform_type' => 'aws_eks_cluster',
                        'outputs'        => [
                            'provider_resource_id' => 'eks_cluster_name',
                            'public_ip'            => 'eks_cluster_endpoint',
                            'metadata'             => [
                                'eks_cluster_endpoint' => 'eks_cluster_endpoint',
                                'eks_cluster_version'  => 'eks_cluster_version',
                            ],
                        ],
                    ],
                    [
                        'name'           => 'akoflow-engine',
                        'terraform_type' => 'aws_instance',
                        'outputs'        => [
                            'provider_resource_id' => 'engine_instance_id',
                            'public_ip'            => 'engine_public_ip',
                            'private_ip'           => 'engine_private_ip',
                            'iframe_url'           => 'akoflow_iframe_url',
                            'metadata'             => [
                                'akoflow_api_url' => 'akoflow_api_url',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function gcpGkeAkoflow(): array
    {
        return [
            'template_version_id' => 2,
            'provider_type'       => 'gcp',
            'module_slug'         => 'akoflow-engine-gke-gcp',
            'main_tf'             => $this->readTerraformFile('gcp_gke_akoflow/main.tf'),
            'variables_tf'        => $this->readTerraformFile('gcp_gke_akoflow/variables.tf'),
            'outputs_tf'          => $this->readTerraformFile('gcp_gke_akoflow/outputs.tf'),
            'tfvars_mapping_json' => [
                'environment_configuration' => [
                    'region'                => 'region',
                    'project_id'            => 'project_id',
                    'gke_version'           => 'gke_version',
                    'node_machine_type'     => 'node_machine_type',
                    'node_count'            => 'node_count',
                    'node_min_count'        => 'node_min_count',
                    'node_max_count'        => 'node_max_count',
                    'gke_enable_autoscaling'=> 'gke_enable_autoscaling',
                    'engine_machine_type'   => 'engine_machine_type',
                    'akoflow_api_port'      => 'akoflow_api_port',
                    'akoflow_allowed_ips'   => 'akoflow_allowed_ips',
                ],
                'instance_configurations' => [
                    'k8s-cluster' => [],
                    'engine-vm'   => [],
                ],
            ],
            'credential_env_keys' => [
                'AKO_GCP_SERVICE_ACCOUNT_KEY',
            ],
            'outputs_mapping_json' => [
                'resources' => [
                    [
                        'name'           => 'gke-cluster',
                        'terraform_type' => 'google_container_cluster',
                        'outputs'        => [
                            'provider_resource_id' => 'gke_cluster_name',
                            'public_ip'            => 'gke_cluster_endpoint',
                            'metadata'             => [
                                'gke_cluster_endpoint' => 'gke_cluster_endpoint',
                                'gke_cluster_version'  => 'gke_cluster_version',
                            ],
                        ],
                    ],
                    [
                        'name'           => 'akoflow-engine',
                        'terraform_type' => 'google_compute_instance',
                        'outputs'        => [
                            'provider_resource_id' => 'engine_instance_name',
                            'public_ip'            => 'engine_public_ip',
                            'private_ip'           => 'engine_private_ip',
                            'iframe_url'           => 'akoflow_iframe_url',
                            'metadata'             => [
                                'akoflow_api_url' => 'akoflow_api_url',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
