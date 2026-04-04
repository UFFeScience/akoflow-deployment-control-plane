<?php

namespace Database\Seeders\Development;

use App\Models\EnvironmentTemplate;
use App\Models\EnvironmentTemplateVersion;
use App\Models\Organization;
use Database\Seeders\Development\TemplateDefinitions\AkoFlowEngineK8sDefinition;
use Database\Seeders\Development\TemplateDefinitions\AkoflowLocalInstallerDefinition;
use Database\Seeders\Development\TemplateDefinitions\AkoflowMulticloudDefinition;
use Database\Seeders\Development\TemplateDefinitions\AwsDockerAnsibleDefinition;
use Database\Seeders\Development\TemplateDefinitions\AwsUbuntuDockerEksDefinition;
use Database\Seeders\Development\TemplateDefinitions\GcpUbuntuDockerGkeDefinition;
use Database\Seeders\Development\TemplateDefinitions\MicroNginxDefinition;
use Illuminate\Database\Seeder;

class TemplatesSeeder extends Seeder
{
    /**
     * Each entry describes one template + version to upsert.
     * Add new templates here – no other changes needed.
     */
    private function templates(int $organizationId): array
    {
        return [
            [
                'slug'                  => 'micro-nginx',
                'name'                  => 'Micro Docker + NGINX (AWS / GCP)',
                'description'           => 'Provisions a single micro VM on AWS or GCP, installs Docker, '
                    . 'and runs an NGINX container. The exposed port is configurable via the deployment settings.',
                'is_public'             => true,
                'owner_organization_id' => $organizationId,
                'version'               => '1.0.0',
                'definition'            => MicroNginxDefinition::get(),
            ],
            [
                'slug'                  => 'akoflow-multicloud',
                'name'                  => 'AkôFlow Multicloud Demo',
                'description'           => 'Provisions an Ubuntu EC2 instance (AkôFlow Workstration) on AWS, an EKS cluster on AWS '
                    . 'and a GKE cluster on GCP. The server auto-installs Docker, configures kubectl for both clusters, '
                    . 'deploys AkôFlow, generates service account tokens, writes the .env and runs the AkôFlow installer.',
                'is_public'             => true,
                'owner_organization_id' => $organizationId,
                'version'               => '1.0.0',
                'definition'            => AkoflowMulticloudDefinition::get(),
            ],
            [
                'slug'                  => 'aws-docker-ansible',
                'name'                  => 'AWS EC2 + Docker (via Ansible)',
                'description'           => 'Provisions an EC2 instance on AWS with Terraform and installs Docker Engine via Ansible. Ideal for testing the Terraform → Ansible pipeline in a cloud environment.',
                'is_public'             => true,
                'owner_organization_id' => $organizationId,
                'version'               => '1.0.0',
                'definition'            => AwsDockerAnsibleDefinition::get(),
            ],
            [
                'slug'                  => 'akoflow-local-installer',
                'name'                  => 'AkôFlow Local Installer (SSH)',
                'description'           => 'Connects to an existing host via SSH, verifies Docker is installed, '
                    . 'builds the AkôFlow Docker image and runs it on the configured port. '
                    . 'Supports password or private-key SSH authentication.',
                'is_public'             => true,
                'owner_organization_id' => $organizationId,
                'version'               => '1.0.0',
                'definition'            => AkoflowLocalInstallerDefinition::get(),
            ],
        ];
    }

    public function run(): void
    {
        $organization = Organization::where('name', 'AkoCloud Demo')->first();
        if (! $organization) {
            return;
        }

        foreach ($this->templates($organization->id) as $data) {
            $template = EnvironmentTemplate::firstOrCreate(
                ['slug' => $data['slug']],
                [
                    'name'                  => $data['name'],
                    'description'           => $data['description'],
                    'is_public'             => $data['is_public'],
                    'owner_organization_id' => $data['owner_organization_id'],
                ]
            );

            $version = EnvironmentTemplateVersion::where('template_id', $template->id)
                ->where('version', $data['version'])
                ->first();

            if ($version) {
                $version->update(['definition_json' => $data['definition'], 'is_active' => true]);
            } else {
                EnvironmentTemplateVersion::create([
                    'template_id'     => $template->id,
                    'version'         => $data['version'],
                    'is_active'       => true,
                    'definition_json' => $data['definition'],
                ]);
            }
        }
    }
}
