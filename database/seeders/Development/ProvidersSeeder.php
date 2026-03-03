<?php

namespace Database\Seeders\Development;

use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProvidersSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            ['name' => 'AWS',  'slug' => 'aws',   'type' => 'CLOUD', 'description' => 'Amazon Web Services — public cloud provider.'],
            ['name' => 'GCP',  'slug' => 'gcp',   'type' => 'CLOUD', 'description' => 'Google Cloud Platform — public cloud provider.'],
            ['name' => 'HPC',  'slug' => 'slurm', 'type' => 'HPC',   'description' => 'On-premises HPC cluster managed via Slurm workload manager.'],
        ];

        foreach ($providers as $provider) {
            Provider::firstOrCreate(
                ['name' => $provider['name']],
                [
                    'slug'          => $provider['slug'],
                    'description'   => $provider['description'],
                    'type'          => $provider['type'],
                    'status'        => 'ACTIVE',
                    'health_status' => 'HEALTHY',
                ]
            );
        }
    }
}
