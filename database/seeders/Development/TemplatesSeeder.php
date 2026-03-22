<?php

namespace Database\Seeders\Development;

use App\Models\EnvironmentTemplate;
use App\Models\EnvironmentTemplateVersion;
use App\Models\Organization;
use Database\Seeders\Development\TemplateDefinitions\AkoflowGkeDefinition;
use Database\Seeders\Development\TemplateDefinitions\NvflareFederatedDefinition;
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
                'slug'                  => 'akoflow-gke',
                'name'                  => 'Workflow Orchestration on Google Kubernetes Engine',
                'description'           => 'AkoFlow orchestrator running on top of a Google Kubernetes Engine deployment. '
                    . 'Includes one GKE node-pool and one AkoFlow compute instance.',
                'is_public'             => true,
                'owner_organization_id' => $organizationId,
                'version'               => '1.0.0',
                'definition'            => AkoflowGkeDefinition::get(),
            ],
            [
                'slug'                  => 'nvflare-federated',
                'name'                  => 'NVIDIA FLARE – Federated Learning',
                'description'           => 'NVIDIA FLARE federated learning deployment. '
                    . 'Includes one FL Server, one Overseer, one DF-Analyse node, '
                    . 'and ten federated client sites – all running as Docker containers on EC2.',
                'is_public'             => true,
                'owner_organization_id' => $organizationId,
                'version'               => '1.0.0',
                'definition'            => NvflareFederatedDefinition::get(),
            ],
            [
                'slug'                  => 'hello-docker',
                'name'                  => 'Hello Docker (AWS/GCP single VM)',
                'description'           => 'Provisiona uma única VM na AWS ou GCP com Docker e executa um script simples.',
                'is_public'             => true,
                'owner_organization_id' => $organizationId,
                'version'               => '1.0.0',
                'definition'            => \Database\Seeders\Development\TemplateDefinitions\HelloWorldDockerDefinition::get(),
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
