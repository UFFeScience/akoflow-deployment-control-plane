<?php

namespace Database\Seeders\Development;

use App\Models\ClusterTemplate;
use App\Models\ExperimentTemplate;
use App\Models\ExperimentTemplateVersion;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class TemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('name', 'AkoCloud Demo')->first();
        if (! $organization) {
            return;
        }

        $template = ExperimentTemplate::firstOrCreate(
            ['slug' => 'demo-template'],
            [
                'name' => 'Demo Template',
                'runtime_type' => 'AKOFLOW',
                'description' => 'Template de exemplo para desenvolvimento',
                'is_public' => false,
                'owner_organization_id' => $organization->id,
            ]
        );

        $templateVersion = ExperimentTemplateVersion::firstOrCreate(
            [
                'template_id' => $template->id,
                'version' => '1.0.0',
            ],
            [
                'definition_json' => [
                    'description' => 'Versao inicial de desenvolvimento',
                    'parameters' => [
                        'instances' => 2,
                        'region' => 'us-east-1',
                    ],
                ],
                'is_active' => true,
            ]
        );

        ClusterTemplate::firstOrCreate(
            ['template_version_id' => $templateVersion->id],
            [
                'custom_parameters_json' => [
                    'scaling' => 'manual',
                ],
            ]
        );
    }
}
