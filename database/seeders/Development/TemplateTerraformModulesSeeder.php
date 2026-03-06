<?php

namespace Database\Seeders\Development;

use App\Models\ExperimentTemplate;
use App\Models\ExperimentTemplateTerraformModule;
use Illuminate\Database\Seeder;

/**
 * Seeds ExperimentTemplateTerraformModule for the two built-in templates.
 *
 * HCL content is embedded directly in this seeder — it is the single source
 * of truth for built-in Terraform modules. No files from infra/terraform/ are
 * read at runtime; everything lives in the database after seeding.
 *
 * tfvars_mapping_json maps experiment configuration_json fields → Terraform
 * variable names, with optional type cast declarations.
 */
class TemplateTerraformModulesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->modules() as $templateSlug => $moduleData) {
            $template = ExperimentTemplate::where('slug', $templateSlug)->first();

            if (! $template) {
                $this->command->warn("Template '{$templateSlug}' not found – skipping terraform module seed.");
                continue;
            }

            $version = $template->versions()->latest('created_at')->first();

            if (! $version) {
                $this->command->warn("Template '{$templateSlug}' has no versions – skipping.");
                continue;
            }

            ExperimentTemplateTerraformModule::updateOrCreate(
                ['template_version_id' => $version->id],
                array_merge($moduleData, ['template_version_id' => $version->id]),
            );

            $this->command->info("TerraformModule seeded for template '{$templateSlug}' (version {$version->id}).");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function modules(): array
    {
        return [
            'nvflare-federated' => $this->awsNvflare(),
            'akoflow-gke'       => $this->gcpGke(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AWS – NVFlare Federated Learning
    // ─────────────────────────────────────────────────────────────────────────

    private function awsNvflare(): array
    {
        return [
            'module_slug'         => 'aws_nvflare',
            'provider_type'       => 'aws',
            'main_tf'             => $this->awsNvflareMainTf(),
            'variables_tf'        => $this->awsNvflareVariablesTf(),
            'outputs_tf'          => $this->awsNvflareOutputsTf(),
            'credential_env_keys' => [
                'AWS_ACCESS_KEY_ID',
                'AWS_SECRET_ACCESS_KEY',
                'AWS_SESSION_TOKEN',
            ],
            'tfvars_mapping_json' => [
                'experiment_configuration' => [
                    'nvflare_version' => 'nvflare_version',
                    'fl_rounds'       => ['tf_var' => 'fl_rounds',    'cast' => 'int'],
                    'aws_region'      => 'aws_region',
                    'vpc_cidr'        => 'vpc_cidr',
                    'server_port'     => ['tf_var' => 'server_port',   'cast' => 'int'],
                    'admin_port'      => ['tf_var' => 'admin_port',    'cast' => 'int'],
                    'overseer_port'   => ['tf_var' => 'overseer_port', 'cast' => 'int'],
                ],
                'instance_configurations' => [
                    'nvflare-server' => [
                        'instance_type'  => 'server_instance_type',
                        'disk_size_gb'   => ['tf_var' => 'server_disk_size_gb', 'cast' => 'int'],
                        'docker_image'   => 'server_docker_image',
                        'docker_command' => 'server_docker_command',
                        'workspace_dir'  => 'server_workspace_dir',
                    ],
                    'nvflare-overseer' => [
                        'instance_type'  => 'overseer_instance_type',
                        'disk_size_gb'   => ['tf_var' => 'overseer_disk_size_gb', 'cast' => 'int'],
                        'docker_image'   => 'overseer_docker_image',
                        'docker_command' => 'overseer_docker_command',
                        'workspace_dir'  => 'overseer_workspace_dir',
                    ],
                    'nvflare-dfanalyse' => [
                        'instance_type'   => 'dfanalyse_instance_type',
                        'disk_size_gb'    => ['tf_var' => 'dfanalyse_disk_size_gb', 'cast' => 'int'],
                        'docker_image'    => 'dfanalyse_docker_image',
                        'docker_command'  => 'dfanalyse_docker_command',
                        'workspace_dir'   => 'dfanalyse_workspace_dir',
                        'output_bucket'   => 'dfanalyse_output_bucket',
                    ],
                    'nvflare-site' => [
                        'instance_type'      => 'site_instance_type',
                        'disk_size_gb'       => ['tf_var' => 'site_disk_size_gb', 'cast' => 'int'],
                        'docker_image'       => 'site_docker_image',
                        'docker_command'     => 'site_docker_command',
                        'workspace_dir'      => 'site_workspace_dir',
                        'local_dataset_path' => 'site_local_dataset_path',
                    ],
                ],
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GCP – AkoFlow on GKE
    // ─────────────────────────────────────────────────────────────────────────

    private function gcpGke(): array
    {
        return [
            'module_slug'         => 'gcp_gke',
            'provider_type'       => 'gcp',
            'main_tf'             => $this->gcpGkeMainTf(),
            'variables_tf'        => $this->gcpGkeVariablesTf(),
            'outputs_tf'          => $this->gcpGkeOutputsTf(),
            'credential_env_keys' => [
                'GOOGLE_CREDENTIALS',
                'GOOGLE_PROJECT',
            ],
            'tfvars_mapping_json' => [
                'experiment_configuration' => [
                    'gcp_project_id'           => 'gcp_project_id',
                    'gcp_region'               => 'gcp_region',
                    'gke_version'              => 'gke_version',
                    'akoflow_bootstrap_command' => 'akoflow_bootstrap_command',
                    'akoflow_allowed_ips'       => 'akoflow_allowed_ips',
                ],
                'instance_configurations' => [
                    'gke-compute' => [
                        'node_count'        => ['tf_var' => 'gke_node_count',        'cast' => 'int'],
                        'machine_type'      => 'gke_machine_type',
                        'disk_size_gb'      => ['tf_var' => 'gke_disk_size_gb',      'cast' => 'int'],
                        'enable_autoscaling' => ['tf_var' => 'gke_enable_autoscaling', 'cast' => 'bool'],
                        'min_nodes'         => ['tf_var' => 'gke_min_nodes',         'cast' => 'int'],
                        'max_nodes'         => ['tf_var' => 'gke_max_nodes',         'cast' => 'int'],
                    ],
                    'akoflow-compute' => [
                        'akoflow_version'  => 'akoflow_version',
                        'deployment_mode'  => 'akoflow_deployment_mode',
                        'replicas'         => ['tf_var' => 'akoflow_replicas',         'cast' => 'int'],
                        'machine_type'     => 'akoflow_machine_type',
                        'disk_size_gb'     => ['tf_var' => 'akoflow_disk_size_gb',     'cast' => 'int'],
                        'enable_public_ip' => ['tf_var' => 'akoflow_enable_public_ip', 'cast' => 'bool'],
                        'api_port'         => ['tf_var' => 'akoflow_api_port',         'cast' => 'int'],
                        'enable_https'     => ['tf_var' => 'akoflow_enable_https',     'cast' => 'bool'],
                    ],
                ],
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HCL file readers — aws_nvflare
    // ─────────────────────────────────────────────────────────────────────────

    private function awsNvflareMainTf(): string
    {
        return file_get_contents(__DIR__ . '/TemplateDefinitions/terraform/modules/aws_nvflare/main.tf');
    }

    private function awsNvflareVariablesTf(): string
    {
        return file_get_contents(__DIR__ . '/TemplateDefinitions/terraform/modules/aws_nvflare/variables.tf');
    }

    private function awsNvflareOutputsTf(): string
    {
        return file_get_contents(__DIR__ . '/TemplateDefinitions/terraform/modules/aws_nvflare/outputs.tf');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HCL file readers — gcp_gke
    // ─────────────────────────────────────────────────────────────────────────

    private function gcpGkeMainTf(): string
    {
        return file_get_contents(__DIR__ . '/TemplateDefinitions/terraform/modules/gcp_gke/main.tf');
    }

    private function gcpGkeVariablesTf(): string
    {
        return file_get_contents(__DIR__ . '/TemplateDefinitions/terraform/modules/gcp_gke/variables.tf');
    }

    private function gcpGkeOutputsTf(): string
    {
        return file_get_contents(__DIR__ . '/TemplateDefinitions/terraform/modules/gcp_gke/outputs.tf');
    }
}
