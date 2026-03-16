<?php

namespace Database\Seeders\Development;

use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProvidersSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'name'                => 'AWS',
                'slug'                => 'aws',
                'default_module_slug' => 'aws_nvflare',
                'type'                => 'CLOUD',
                'description'         => 'Amazon Web Services — public cloud provider.',
            ],
            [
                'name'                => 'GCP',
                'slug'                => 'gcp',
                'default_module_slug' => 'gcp_gke',
                'type'                => 'CLOUD',
                'description'         => 'Google Cloud Platform — public cloud provider.',
            ],
            [
                'name'                => 'HPC',
                'slug'                => 'slurm',
                'default_module_slug' => null,
                'type'                => 'HPC',
                'description'         => 'On-premises HPC cluster managed via Slurm workload manager.',
            ],
        ];

        foreach ($providers as $provider) {
            Provider::firstOrCreate(
                ['name' => $provider['name']],
                [
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
