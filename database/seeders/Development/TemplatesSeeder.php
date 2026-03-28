<?php

namespace Database\Seeders\Development;

use App\Models\EnvironmentTemplate;
use App\Models\EnvironmentTemplateVersion;
use App\Models\Organization;
use Database\Seeders\Development\TemplateDefinitions\AkoFlowEngineK8sDefinition;
use Database\Seeders\Development\TemplateDefinitions\AkoflowMulticloudDefinition;
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
                'slug'                  => 'ubuntu-docker-eks',
                'name'                  => 'Ubuntu + Docker VM + EKS Cluster (AWS)',
                'description'           => 'Provisions an Ubuntu 22.04 EC2 instance with Docker CE and an Amazon EKS cluster '
                    . 'inside a dedicated VPC. Ideal for teams that need both a general-purpose Docker host and a managed Kubernetes cluster.',
                'is_public'             => true,
                'owner_organization_id' => $organizationId,
                'version'               => '1.0.0',
                'definition'            => AwsUbuntuDockerEksDefinition::get(),
            ],
            [
                'slug'                  => 'ubuntu-docker-gke',
                'name'                  => 'Ubuntu + Docker VM + GKE Cluster (GCP)',
                'description'           => 'Provisions an Ubuntu 22.04 LTS Compute Engine instance with Docker CE and a Google '
                    . 'Kubernetes Engine (GKE) cluster inside a dedicated VPC-native network. Perfect for teams that need '
                    . 'a general-purpose Docker host alongside a managed Kubernetes cluster on GCP.',
                'is_public'             => true,
                'owner_organization_id' => $organizationId,
                'version'               => '1.0.0',
                'definition'            => GcpUbuntuDockerGkeDefinition::get(),
            ],
            [
                'slug'                  => 'akoflow-multicloud',
                'name'                  => 'AkoFlow — EKS (AWS) + GKE (GCP) + Server',
                'description'           => 'Provisions an Ubuntu EC2 instance (AkoFlow server) on AWS, an EKS cluster on AWS '
                    . 'and a GKE cluster on GCP. The server auto-installs Docker, configures kubectl for both clusters, '
                    . 'deploys AkoFlow, generates service account tokens, writes the .env and runs the AkoFlow installer.',
                'is_public'             => true,
                'owner_organization_id' => $organizationId,
                'version'               => '1.0.0',
                'definition'            => AkoflowMulticloudDefinition::get(),
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
