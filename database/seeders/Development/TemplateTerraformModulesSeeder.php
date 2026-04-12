<?php

namespace Database\Seeders\Development;

use App\Models\EnvironmentTemplate;
use App\Models\EnvironmentTemplateVersion;
use App\Models\EnvironmentTemplateProviderConfiguration;
use App\Models\EnvironmentTemplateTerraformModule;
use Illuminate\Database\Seeder;

class TemplateTerraformModulesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->modules() as $module) {
            $versionId = $this->resolveVersionId($module['template_slug'], $module['template_version']);

            if ($versionId === null) {
                $this->command->warn(
                    "Skipping '{$module['module_slug']}': template '{$module['template_slug']}' v{$module['template_version']} not found."
                );
                continue;
            }

            $providerType = strtoupper($module['provider_type']);

            $config = EnvironmentTemplateProviderConfiguration::firstOrCreate(
                ['template_version_id' => $versionId, 'name' => $providerType],
                ['applies_to_providers' => [$providerType]],
            );

            EnvironmentTemplateTerraformModule::updateOrCreate(
                ['provider_configuration_id' => $config->id],
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

    private function resolveVersionId(string $templateSlug, string $version): ?int
    {
        $template = EnvironmentTemplate::where('slug', $templateSlug)->first();
        if (! $template) {
            return null;
        }

        return EnvironmentTemplateVersion::where('template_id', $template->id)
            ->where('version', $version)
            ->value('id');
    }

    private function modules(): array
    {
        return [
            $this->awsMicroNginx(),
            $this->gcpMicroNginx(),
            // $this->awsUbuntuDockerEks(),
            // $this->gcpUbuntuDockerGke(),
            $this->sscad2025FedLearning(),
            $this->akoflowMulticloud(),
            $this->awsDockerAnsible(),
            $this->localAkoflowInstaller(),
        ];
    }

    private function readTerraformFile(string $relativePath): string
    {
        $basePath = database_path('seeders/Development/TemplateDefinitions/terraform/modules');
        $fullPath  = $basePath . DIRECTORY_SEPARATOR . $relativePath;

        return (string) file_get_contents($fullPath);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function awsMicroNginx(): array
    {
        return [
            'template_slug'    => 'micro-nginx',
            'template_version' => '1.0.0',
            'provider_type'    => 'aws',
            'module_slug'      => 'micro-nginx-aws',
            'main_tf'          => $this->readTerraformFile('hello_aws/main.tf'),
            'variables_tf'     => $this->readTerraformFile('hello_aws/variables.tf'),
            'outputs_tf'       => $this->readTerraformFile('hello_aws/outputs.tf'),
            'tfvars_mapping_json' => [
                'environment_configuration' => [
                    'region'        => 'region',
                    'zone'          => 'zone',
                    'instance_type' => 'instance_type',
                    'nginx_port'    => 'nginx_port',
                    'key_name'      => 'key_name',
                ],
                'instance_configurations' => ['single-vm' => []],
            ],
            'credential_env_keys'  => [],
            'outputs_mapping_json' => [
                'resources' => [[
                    'name'           => 'nginx-vm',
                    'terraform_type' => 'aws_instance',
                    'outputs'        => [
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
                ]],
            ],
        ];
    }

    private function gcpMicroNginx(): array
    {
        return [
            'template_slug'    => 'micro-nginx',
            'template_version' => '1.0.0',
            'provider_type'    => 'gcp',
            'module_slug'      => 'micro-nginx-gcp',
            'main_tf'          => $this->readTerraformFile('hello_gcp/main.tf'),
            'variables_tf'     => $this->readTerraformFile('hello_gcp/variables.tf'),
            'outputs_tf'       => $this->readTerraformFile('hello_gcp/outputs.tf'),
            'tfvars_mapping_json' => [
                'environment_configuration' => [
                    'region'         => 'region',
                    'zone'           => 'zone',
                    'project_id'     => 'project_id',
                    'machine_type'   => 'machine_type',
                    'nginx_port'     => 'nginx_port',
                    'ssh_public_key' => 'ssh_public_key',
                    'ssh_user'       => 'ssh_user',
                ],
                'instance_configurations' => ['single-vm' => []],
            ],
            'credential_env_keys'  => ['GOOGLE_CREDENTIALS', 'GOOGLE_PROJECT', 'GOOGLE_REGION'],
            'outputs_mapping_json' => [
                'resources' => [[
                    'name'           => 'nginx-vm',
                    'terraform_type' => 'google_compute_instance',
                    'outputs'        => [
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
                ]],
            ],
        ];
    }

    private function sscad2025FedLearning(): array
    {
        $siteResources = [];
        for ($i = 1; $i <= 10; $i++) {
            $siteResources[] = [
                'name'           => "site-{$i}",
                'terraform_type' => 'google_compute_instance',
                'outputs'        => [
                    'provider_resource_id' => "site_{$i}_name",
                    'public_ip'            => "site_{$i}_public_ip",
                    'private_ip'           => "site_{$i}_private_ip",
                    'metadata'             => [
                        'role' => 'site',
                    ],
                ],
            ];
        }

        return [
            'template_slug'    => 'sscad-2025-fed-learning',
            'template_version' => '1.0.0',
            'provider_type'    => 'gcp',
            'module_slug'      => 'sscad-2025-fed-learning-gcp',
            'main_tf'          => $this->readTerraformFile('sscad_2025_fed_learning_gcp/main.tf'),
            'variables_tf'     => $this->readTerraformFile('sscad_2025_fed_learning_gcp/variables.tf'),
            'outputs_tf'       => $this->readTerraformFile('sscad_2025_fed_learning_gcp/outputs.tf'),
            'tfvars_mapping_json' => [
                'environment_configuration' => [
                    'project_id'           => 'project_id',
                    'region'               => 'region',
                    'zone'                 => 'zone',
                    'network_name'         => 'network_name',
                    'subnet_name'          => 'subnet_name',
                    'image_id'             => 'image_id',
                    'ssh_public_key'       => 'ssh_public_key',
                    'ssh_user'             => 'ssh_user',
                    'experiment_name'      => 'experiment_name',
                    'description'          => 'description',
                    'algorithm'            => 'algorithm',
                    'clients'              => 'clients',
                    'dataset_folder_key'   => 'dataset_folder_key',
                    'site_folder_url'      => 'site_folder_url',
                    'dfanalyse_machine_type' => 'dfanalyse_machine_type',
                    'overseer_machine_type' => 'overseer_machine_type',
                    'server_machine_type'  => 'server_machine_type',
                    'site_machine_type'    => 'site_machine_type',
                ],
                'instance_configurations' => [],
            ],
            'credential_env_keys'  => [],
            'outputs_mapping_json' => [
                'resources' => array_merge([
                    [
                        'name'           => 'dfanalyse',
                        'terraform_type' => 'google_compute_instance',
                        'outputs'        => [
                            'provider_resource_id' => 'dfanalyse_name',
                            'public_ip'            => 'dfanalyse_public_ip',
                            'private_ip'           => 'dfanalyse_private_ip',
                        ],
                    ],
                    [
                        'name'           => 'overseer',
                        'terraform_type' => 'google_compute_instance',
                        'outputs'        => [
                            'provider_resource_id' => 'overseer_name',
                            'public_ip'            => 'overseer_public_ip',
                            'private_ip'           => 'overseer_private_ip',
                        ],
                    ],
                    [
                        'name'           => 'server',
                        'terraform_type' => 'google_compute_instance',
                        'outputs'        => [
                            'provider_resource_id' => 'server_name',
                            'public_ip'            => 'server_public_ip',
                            'private_ip'           => 'server_private_ip',
                        ],
                    ],
                ], $siteResources),
            ],
        ];
    }


    private function akoflowMulticloud(): array
    {
        return [
            'template_slug'    => 'akoflow-multicloud',
            'template_version' => '1.0.0',
            'provider_type'    => 'custom',
            'module_slug'      => 'akoflow-multicloud-aws-gcp',
            'main_tf'          => $this->readTerraformFile('akoflow_multicloud_aws_gcp/main.tf'),
            'variables_tf'     => $this->readTerraformFile('akoflow_multicloud_aws_gcp/variables.tf'),
            'outputs_tf'       => $this->readTerraformFile('akoflow_multicloud_aws_gcp/outputs.tf'),
            'tfvars_mapping_json' => [
                'environment_configuration' => [
                    'aws_region'             => 'aws_region',
                    'aws_vpc_cidr'           => 'aws_vpc_cidr',
                    'aws_subnet_1_cidr'      => 'aws_subnet_1_cidr',
                    'aws_subnet_2_cidr'      => 'aws_subnet_2_cidr',
                    'ec2_instance_type'      => 'ec2_instance_type',
                    'key_name'               => 'key_name',
                    'eks_kubernetes_version' => 'eks_kubernetes_version',
                    'eks_node_instance_type' => 'eks_node_instance_type',
                    'eks_desired_nodes'      => 'eks_desired_nodes',
                    'eks_min_nodes'          => 'eks_min_nodes',
                    'eks_max_nodes'          => 'eks_max_nodes',
                    'gcp_project_id'         => 'gcp_project_id',
                    'gcp_region'             => 'gcp_region',
                    'gcp_sa_key_json'        => 'gcp_sa_key_json',
                    'gcp_subnet_cidr'        => 'gcp_subnet_cidr',
                    'gcp_pods_cidr'          => 'gcp_pods_cidr',
                    'gcp_services_cidr'      => 'gcp_services_cidr',
                    'gke_kubernetes_version' => 'gke_kubernetes_version',
                    'gke_node_machine_type'  => 'gke_node_machine_type',
                    'gke_desired_nodes'      => 'gke_desired_nodes',
                    'gke_min_nodes'          => 'gke_min_nodes',
                    'gke_max_nodes'          => 'gke_max_nodes',
                ],
                'instance_configurations' => [],
            ],
            'credential_env_keys'  => ['GOOGLE_CREDENTIALS', 'GOOGLE_PROJECT', 'GOOGLE_REGION'],
            'outputs_mapping_json' => [
                'resources' => [
                    [
                        'name'           => 'akoflow-server',
                        'terraform_type' => 'aws_instance',
                        'outputs'        => [
                            'provider_resource_id' => 'akoflow_instance_id',
                            'public_ip'            => 'public_ip',
                            'private_ip'           => 'private_ip',
                            'iframe_url'           => 'akoflow_iframe_url',
                            'metadata'             => [
                                'setup_log_hint'   => 'setup_log_hint',
                                'aws_vpc_id'       => 'aws_vpc_id',
                                'gcp_network_name' => 'gcp_network_name',
                            ],
                        ],
                    ],
                    [
                        'name'           => 'eks-cluster',
                        'terraform_type' => 'aws_eks_cluster',
                        'outputs'        => [
                            'provider_resource_id' => 'eks_cluster_arn',
                            'public_ip'            => '',
                            'private_ip'           => '',
                            'iframe_url'           => '',
                            'metadata'             => [
                                'eks_cluster_name'       => 'eks_cluster_name',
                                'eks_cluster_endpoint'   => 'eks_cluster_endpoint',
                                'eks_kubernetes_version' => 'eks_kubernetes_version',
                            ],
                        ],
                    ],
                    [
                        'name'           => 'gke-cluster',
                        'terraform_type' => 'google_container_cluster',
                        'outputs'        => [
                            'provider_resource_id' => 'gke_cluster_name',
                            'public_ip'            => '',
                            'private_ip'           => '',
                            'iframe_url'           => '',
                            'metadata'             => [
                                'gke_cluster_name'       => 'gke_cluster_name',
                                'gke_cluster_endpoint'   => 'gke_cluster_endpoint',
                                'gke_kubernetes_version' => 'gke_kubernetes_version',
                                'gke_node_pool_name'     => 'gke_node_pool_name',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function awsDockerAnsible(): array
    {
        return [
            'template_slug'    => 'aws-docker-ansible',
            'template_version' => '1.0.0',
            'provider_type'    => 'aws',
            'module_slug'      => 'aws-docker-ansible',
            'main_tf'          => $this->readTerraformFile('aws_ubuntu_ansible/main.tf'),
            'variables_tf'     => $this->readTerraformFile('aws_ubuntu_ansible/variables.tf'),
            'outputs_tf'       => $this->readTerraformFile('aws_ubuntu_ansible/outputs.tf'),
            'tfvars_mapping_json' => [
                'environment_configuration' => [
                    'region'        => 'region',
                    'instance_type' => 'instance_type',
                    'key_name'      => 'key_name',
                ],
                'instance_configurations' => [],
            ],
            'credential_env_keys'  => [],
            'outputs_mapping_json' => [
                'resources' => [
                    [
                        'name'           => 'docker-ec2',
                        'terraform_type' => 'aws_instance',
                        'outputs'        => [
                            'provider_resource_id' => 'instance_id',
                            'public_ip'            => 'public_ip',
                            'private_ip'           => 'private_ip',
                            'iframe_url'           => '',
                            'metadata'             => [
                                'security_group_id' => 'security_group_id',
                                'resolved_ami'      => 'resolved_ami',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function localAkoflowInstaller(): array
    {
        return [
            'template_slug'    => 'akoflow-local-installer',
            'template_version' => '1.0.0',
            'provider_type'    => 'local',
            'module_slug'      => 'akoflow-local-installer',
            'main_tf'          => $this->readTerraformFile('akoflow_local_installer/main.tf'),
            'variables_tf'     => $this->readTerraformFile('akoflow_local_installer/variables.tf'),
            'outputs_tf'       => $this->readTerraformFile('akoflow_local_installer/outputs.tf'),

            // akoflow_port comes from environment config; host/user/ssh_* come from
            // credential env vars (TF_VAR_host, TF_VAR_user, TF_VAR_ssh_password,
            // TF_VAR_ssh_private_key) injected by ProviderCredentialResolverService.
            'tfvars_mapping_json' => [
                'environment_configuration' => [
                    'akoflow_port' => 'akoflow_port',
                ],
                'instance_configurations' => [],
            ],

            'credential_env_keys' => ['SSH_PRIVATE_KEY', 'SSH_PASSWORD'],

            'outputs_mapping_json' => [
                'resources' => [
                    [
                        'name'           => 'akoflow-host',
                        'terraform_type' => 'null_resource',
                        'outputs'        => [
                            'provider_resource_id' => 'host',
                            'public_ip'            => 'host',
                            'private_ip'           => 'host',
                            'iframe_url'           => 'akoflow_url',
                            'metadata'             => [
                                'akoflow_url' => 'akoflow_url',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
