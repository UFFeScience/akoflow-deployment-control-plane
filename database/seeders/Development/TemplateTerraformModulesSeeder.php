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
                    'module_slug' => $module['module_slug'],
                    'main_tf' => $module['main_tf'],
                    'variables_tf' => $module['variables_tf'],
                    'outputs_tf' => $module['outputs_tf'],
                    'tfvars_mapping_json' => $module['tfvars_mapping_json'],
                    'credential_env_keys' => $module['credential_env_keys'],
                ],
            );
        }
    }

    private function modules(): array
    {
        return [
            $this->awsNvflare(),
            $this->gcpGke(),
            $this->awsHelloDocker(),
            $this->gcpHelloDocker(),
        ];
    }

    private function awsNvflare(): array
    {
        return [
            'template_version_id' => 1,
            'provider_type' => 'aws',
            'module_slug' => 'nvflare-aws',
            'main_tf' => $this->readTerraformFile('aws_nvflare/main.tf'),
            'variables_tf' => $this->readTerraformFile('aws_nvflare/variables.tf'),
            'outputs_tf' => $this->readTerraformFile('aws_nvflare/outputs.tf'),
            'tfvars_mapping_json' => [
                'cluster_name' => 'cluster_name',
                'region' => 'region',
                'node_count' => 'node_count',
                'node_size' => 'node_size',
            ],
            'credential_env_keys' => [
                'AKO_AWS_ACCESS_KEY_ID',
                'AKO_AWS_SECRET_ACCESS_KEY',
            ],
        ];
    }

    private function gcpGke(): array
    {
        return [
            'template_version_id' => 2,
            'provider_type' => 'gcp',
            'module_slug' => 'gke-basic',
            'main_tf' => $this->readTerraformFile('gcp_gke/main.tf'),
            'variables_tf' => $this->readTerraformFile('gcp_gke/variables.tf'),
            'outputs_tf' => $this->readTerraformFile('gcp_gke/outputs.tf'),
            'tfvars_mapping_json' => [
                'project_id' => 'project_id',
                'region' => 'region',
                'cluster_name' => 'cluster_name',
                'node_count' => 'node_count',
                'node_size' => 'node_size',
            ],
            'credential_env_keys' => [
                'AKO_GCP_SERVICE_ACCOUNT_KEY',
            ],
        ];
    }

    private function awsHelloDocker(): array
    {
        return [
            'template_version_id' => 3,
            'provider_type' => 'aws',
            'module_slug' => 'hello-docker-aws',
            'main_tf' => $this->readTerraformFile('hello_aws/main.tf'),
            'variables_tf' => $this->readTerraformFile('hello_aws/variables.tf'),
            'outputs_tf' => $this->readTerraformFile('hello_aws/outputs.tf'),
            'tfvars_mapping_json' => [
                'environment_configuration' => [
                    'cloud_provider'  => 'cloud_provider',
                    'region'          => 'region',
                    'zone'            => 'zone',
                    'instance_type'   => 'instance_type',
                    'ami_id'          => 'ami_id',
                    'startup_script'  => 'startup_script',
                ],
                'instance_configurations' => [
                    'single-vm' => [],
                ],
            ],
            'credential_env_keys' => [
                'AKO_AWS_ACCESS_KEY_ID',
                'AKO_AWS_SECRET_ACCESS_KEY',
            ],
        ];
    }

    private function gcpHelloDocker(): array
    {
        return [
            'template_version_id' => 3,
            'provider_type' => 'gcp',
            'module_slug' => 'hello-docker-gcp',
            'main_tf' => $this->readTerraformFile('hello_gcp/main.tf'),
            'variables_tf' => $this->readTerraformFile('hello_gcp/variables.tf'),
            'outputs_tf' => $this->readTerraformFile('hello_gcp/outputs.tf'),
            'tfvars_mapping_json' => [
                'environment_configuration' => [
                    'provider' => 'provider',
                    'region' => 'region',
                    'zone' => 'zone',
                    'machine_type_gcp' => 'machine_type',
                    'image_gcp' => 'image',
                    'startup_script' => 'startup_script',
                ],
                'instance_configurations' => [
                    'single-vm' => [],
                ],
            ],
            'credential_env_keys' => [
                'AKO_GCP_SERVICE_ACCOUNT_KEY',
            ],
        ];
    }

    private function readTerraformFile(string $relativePath): string
    {
        $basePath = database_path('seeders/Development/TemplateDefinitions/terraform/modules');
        $fullPath = $basePath . DIRECTORY_SEPARATOR . $relativePath;

        return (string) file_get_contents($fullPath);
    }
}
