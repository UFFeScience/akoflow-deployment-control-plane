<?php

namespace Database\Seeders\Development;

use App\Models\Organization;
use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProvidersSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('name', 'AkoCloud Demo')->first();
        if (! $organization) {
            return;
        }

        $providers = [
            [
                'name'                => 'AWS',
                'slug'                => 'aws',
                'default_module_slug' => 'aws_nvflare',
                'type'                => 'AWS',
                'description'         => 'Amazon Web Services — public cloud provider.',
            ],
            [
                'name'                => 'GCP',
                'slug'                => 'gcp',
                'default_module_slug' => 'gcp_gke',
                'type'                => 'GCP',
                'description'         => 'Google Cloud Platform — public cloud provider.',
            ],
            [
                'name'                => 'HPC',
                'slug'                => 'slurm',
                'default_module_slug' => null,
                'type'                => 'HPC',
                'description'         => 'On-premises HPC deployment managed via Slurm workload manager.',
            ],
        ];

        foreach ($providers as $provider) {
            Provider::updateOrCreate(
                ['organization_id' => $organization->id, 'name' => $provider['name']],
                [
                    'organization_id'     => $organization->id,
                    'slug'                => $provider['slug'],
                    'default_module_slug' => $provider['default_module_slug'],
                    'description'         => $provider['description'],
                    'type'                => $provider['type'],
                    'status'              => 'ACTIVE',
                    'health_status'       => 'HEALTHY',
                ]
            );
        }
    }
}
